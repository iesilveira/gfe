#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Atualizador GFE/SMPM — versão 17.14.5.

Base:
- GFE/SMPM 17.14.4.

Correções:
- Corrige a tela de Acesso não autorizado que desaparecia.
- Remove a classe genérica "wrap" da página de acesso negado.
- Usa um contêiner exclusivo para a página de acesso negado.
- Limita a personalização do Painel à tela dashboard.
- Remove a ocultação genérica de todos os elementos .wrap.
- Oculta somente o conteúdo nativo do Painel.
- Reforça a exibição da página de acesso negado.
- Mantém a mensagem configurável pelos administradores.
- Preserva o marcador {nome}.
- Preserva todos os bloqueios de acesso da versão 17.14.4.
- Preserva todas as correções anteriores.
- Atualiza Version, SMPM_VERSION e Stable tag.
- Gera gfe-17.14.5.zip.
- Não cria backup.
"""

from __future__ import annotations

import re
import shutil
import subprocess
import sys
import zipfile
from pathlib import Path


TARGET_VERSION = "17.14.5"

EXPECTED_BASE_VERSIONS = {
    "17.14.4",
    "17.14.5",
}

ROOT_DIR = Path(
    r"C:\Users\x17821234\Documents\GitHub"
)

PLUGIN_DIR = ROOT_DIR / "gfe"

MAIN_FILE = (
    PLUGIN_DIR
    / "saude-mg-permission-manager.php"
)

CORE_FILE = (
    PLUGIN_DIR
    / "includes"
    / "class-saude-mg-permission-manager.php"
)

ADMIN_FILE = (
    PLUGIN_DIR
    / "admin"
    / "class-saude-mg-permission-manager-admin.php"
)

README_FILE = (
    PLUGIN_DIR
    / "readme.txt"
)

ZIP_FILE = (
    ROOT_DIR
    / f"gfe-{TARGET_VERSION}.zip"
)

REQUIRED_FILES = (
    MAIN_FILE,
    CORE_FILE,
    ADMIN_FILE,
    README_FILE,
)


class UpdateError(RuntimeError):
    """Erro controlado durante a atualização."""


def read_text(path: Path) -> str:
    """Lê um arquivo em UTF-8."""

    try:
        return path.read_text(
            encoding="utf-8",
        )
    except UnicodeDecodeError:
        return path.read_text(
            encoding="utf-8-sig",
        )
    except OSError as exc:
        raise UpdateError(
            f"Não foi possível ler '{path}': {exc}"
        ) from exc


def write_text(
    path: Path,
    content: str,
) -> None:
    """Grava um arquivo em UTF-8 sem BOM."""

    try:
        path.write_text(
            content,
            encoding="utf-8",
        )
    except OSError as exc:
        raise UpdateError(
            f"Não foi possível gravar '{path}': {exc}"
        ) from exc


def get_newline(content: str) -> str:
    """Identifica o padrão de quebra de linha."""

    if "\r\n" in content:
        return "\r\n"

    return "\n"


def normalize_block(
    block: str,
    newline: str,
) -> str:
    """Normaliza as quebras de linha."""

    return (
        block.strip("\n")
        .replace("\r\n", "\n")
        .replace("\n", newline)
    )


def validate_files() -> None:
    """Valida a estrutura básica do plugin."""

    if not PLUGIN_DIR.is_dir():
        raise UpdateError(
            "Pasta do plugin não encontrada:\n"
            f"{PLUGIN_DIR}"
        )

    missing_files = [
        str(path.relative_to(PLUGIN_DIR))
        for path in REQUIRED_FILES
        if not path.is_file()
    ]

    if missing_files:
        raise UpdateError(
            "Arquivos obrigatórios ausentes:\n- "
            + "\n- ".join(missing_files)
        )

    print(
        "  [OK] Estrutura do plugin validada."
    )


def detect_plugin_version(
    content: str,
) -> str:
    """Obtém a versão declarada no cabeçalho."""

    match = re.search(
        r"(?mi)^\s*\*\s*Version:\s*([^\r\n]+)$",
        content,
    )

    if match is None:
        raise UpdateError(
            "Não foi possível identificar a versão "
            "no cabeçalho do plugin."
        )

    return match.group(1).strip()


def validate_base_version() -> None:
    """Valida a versão da pasta local."""

    current_version = detect_plugin_version(
        read_text(MAIN_FILE)
    )

    if current_version not in EXPECTED_BASE_VERSIONS:
        accepted_versions = ", ".join(
            sorted(EXPECTED_BASE_VERSIONS)
        )

        raise UpdateError(
            "Esta atualização deve ser aplicada sobre "
            "a versão 17.14.4.\n"
            f"Versão encontrada: {current_version}\n"
            f"Versões aceitas: {accepted_versions}"
        )

    print(
        "  [OK] Versão-base encontrada: "
        f"{current_version}"
    )


def find_php_method(
    content: str,
    method_name: str,
) -> tuple[int, int] | None:
    """
    Localiza um método PHP completo considerando:
    - strings;
    - caracteres escapados;
    - comentários de linha;
    - comentários de bloco;
    - chaves internas.
    """

    pattern = re.compile(
        rf"(?m)^[ \t]*"
        rf"(?:public|private|protected)\s+"
        rf"function\s+{re.escape(method_name)}\s*"
        rf"\([^)]*\)\s*\{{"
    )

    match = pattern.search(content)

    if match is None:
        return None

    opening_brace = content.find(
        "{",
        match.start(),
        match.end(),
    )

    if opening_brace < 0:
        raise UpdateError(
            f"Assinatura inválida em {method_name}()."
        )

    index = opening_brace
    depth = 0

    quote: str | None = None
    escaped = False
    line_comment = False
    block_comment = False

    while index < len(content):
        character = content[index]

        next_character = (
            content[index + 1]
            if index + 1 < len(content)
            else ""
        )

        if line_comment:
            if character in "\r\n":
                line_comment = False

            index += 1
            continue

        if block_comment:
            if (
                character == "*"
                and next_character == "/"
            ):
                block_comment = False
                index += 2
                continue

            index += 1
            continue

        if quote is not None:
            if escaped:
                escaped = False

            elif character == "\\":
                escaped = True

            elif character == quote:
                quote = None

            index += 1
            continue

        if (
            character == "/"
            and next_character == "/"
        ):
            line_comment = True
            index += 2
            continue

        if character == "#":
            line_comment = True
            index += 1
            continue

        if (
            character == "/"
            and next_character == "*"
        ):
            block_comment = True
            index += 2
            continue

        if character in ("'", '"'):
            quote = character
            index += 1
            continue

        if character == "{":
            depth += 1

        elif character == "}":
            depth -= 1

            if depth == 0:
                method_end = index + 1

                while (
                    method_end < len(content)
                    and content[method_end] in "\r\n"
                ):
                    method_end += 1

                return match.start(), method_end

        index += 1

    raise UpdateError(
        f"Final de {method_name}() não encontrado."
    )


def replace_php_method(
    content: str,
    method_name: str,
    replacement: str,
) -> tuple[str, bool]:
    """Substitui um método PHP completo."""

    method_range = find_php_method(
        content,
        method_name,
    )

    if method_range is None:
        raise UpdateError(
            "Método não encontrado no arquivo PHP: "
            f"{method_name}()."
        )

    start, end = method_range
    newline = get_newline(content)

    normalized_replacement = (
        normalize_block(
            replacement,
            newline,
        )
        + newline
        + newline
    )

    updated_content = (
        content[:start]
        + normalized_replacement
        + content[end:]
    )

    return (
        updated_content,
        updated_content != content,
    )


CUSTOM_DASHBOARD_CONTENT = r"""
    public function smpm_custom_dashboard_content() {
        /*
         * A mensagem personalizada deve ser executada
         * exclusivamente no Painel principal.
         *
         * get_current_screen() é usado para impedir que
         * esta rotina alcance páginas internas criadas
         * pelo próprio plugin.
         */
        $screen = get_current_screen();

        if (
            ! $screen
            || 'dashboard' !== $screen->base
            || 'dashboard' !== $screen->id
        ) {
            return;
        }

        $current_user = wp_get_current_user();

        if (
            ! $current_user->exists()
            || ! in_array(
                'editor',
                (array) $current_user->roles,
                true
            )
        ) {
            return;
        }

        $allowed_pages = get_user_meta(
            $current_user->ID,
            'smpm_allowed_pages',
            true
        );

        $allowed_pages = is_array( $allowed_pages )
            ? array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'absint',
                            $allowed_pages
                        )
                    )
                )
            )
            : array();

        $allowed_categories = get_user_meta(
            $current_user->ID,
            'smpm_allowed_categories',
            true
        );

        $allowed_categories = is_array(
            $allowed_categories
        )
            ? array_values(
                array_unique(
                    array_filter(
                        array_map(
                            'absint',
                            $allowed_categories
                        )
                    )
                )
            )
            : array();

        $has_pages = ! empty(
            $allowed_pages
        );

        $has_categories = ! empty(
            $allowed_categories
        );

        $first_name = trim(
            (string) $current_user->user_firstname
        );

        if ( '' === $first_name ) {
            $first_name = trim(
                (string) $current_user->display_name
            );
        }

        if ( '' === $first_name ) {
            $first_name = trim(
                (string) $current_user->user_login
            );
        }

        if ( ! $has_pages && ! $has_categories ) {
            $message = get_option(
                'smpm_first_access_message',
                $this->smpm_get_default_first_access_message()
            );
        } else {
            $message = get_option(
                'smpm_dashboard_message',
                $this->smpm_get_default_dashboard_message()
            );
        }

        $message = str_replace(
            '{nome}',
            esc_html( $first_name ),
            (string) $message
        );

        echo '<div id="smpm-custom-dashboard" '
            . 'style="'
            . 'display:block;'
            . 'visibility:visible;'
            . 'opacity:1;'
            . 'margin:20px 20px 0 0;'
            . 'background:#fff;'
            . 'padding:20px;'
            . 'border:1px solid #ccd0d4;'
            . 'box-shadow:0 1px 1px '
            . 'rgba(0,0,0,.04);'
            . '">';

        echo wp_kses_post(
            wpautop( $message )
        );

        echo '</div>';

        /*
         * Oculta somente os componentes nativos do Painel.
         *
         * Não utiliza mais:
         * $("#wpbody-content .wrap").hide()
         *
         * Essa regra era genérica demais e poderia alcançar
         * páginas administrativas internas.
         */
        echo '<script id="smpm-dashboard-script">
            jQuery(function ($) {
                var customDashboard = $(
                    "#smpm-custom-dashboard"
                );

                if (!customDashboard.length) {
                    return;
                }

                $("#welcome-panel").hide();
                $("#dashboard-widgets-wrap").hide();

                customDashboard
                    .prependTo("#wpbody-content")
                    .css({
                        display: "block",
                        visibility: "visible",
                        opacity: 1
                    });
            });
        </script>';
    }
"""


RENDER_ACCESS_DENIED_PAGE = r"""
    public function smpm_render_access_denied_page() {
        $current_user = wp_get_current_user();

        /*
         * A tela é destinada exclusivamente aos Editores.
         * Outros perfis retornam ao Painel.
         */
        if (
            ! $current_user->exists()
            || ! in_array(
                'editor',
                (array) $current_user->roles,
                true
            )
        ) {
            wp_safe_redirect(
                admin_url( 'index.php' )
            );

            exit;
        }

        $first_name = trim(
            (string) $current_user->user_firstname
        );

        if ( '' === $first_name ) {
            $first_name = trim(
                (string) $current_user->display_name
            );
        }

        if ( '' === $first_name ) {
            $first_name = trim(
                (string) $current_user->user_login
            );
        }

        $message = get_option(
            'smpm_access_denied_message',
            $this->smpm_get_default_access_denied_message()
        );

        $message = str_replace(
            '{nome}',
            esc_html( $first_name ),
            (string) $message
        );

        /*
         * Não utiliza a classe "wrap".
         *
         * Isso impede que scripts destinados ao Painel
         * ocultem acidentalmente esta página.
         */
        ?>
        <div
            id="smpm-access-denied-page"
            class="smpm-access-denied-page"
        >
            <div
                id="smpm-access-denied-card"
                class="notice notice-error"
            >
                <div
                    class="smpm-access-denied-message"
                >
                    <?php
                    echo wp_kses_post(
                        wpautop( $message )
                    );
                    ?>
                </div>

                <p class="smpm-access-denied-actions">
                    <a
                        href="<?php echo esc_url(
                            admin_url( 'index.php' )
                        ); ?>"
                        class="button button-primary"
                    >
                        <?php esc_html_e(
                            'Voltar ao Painel',
                            'saude-mg-permission-manager'
                        ); ?>
                    </a>
                </p>
            </div>
        </div>

        <style id="smpm-access-denied-styles">
            body.toplevel_page_smpm-access-denied
            #wpbody-content,
            body.admin_page_smpm-access-denied
            #wpbody-content {
                display: block !important;
                min-height: 400px !important;
                opacity: 1 !important;
                visibility: visible !important;
            }

            #smpm-access-denied-page {
                box-sizing: border-box !important;
                display: block !important;
                margin: 20px 20px 0 0 !important;
                max-width: 960px !important;
                opacity: 1 !important;
                position: relative !important;
                visibility: visible !important;
            }

            #smpm-access-denied-card {
                background: #fff !important;
                border-left-color: #d63638 !important;
                border-left-width: 4px !important;
                box-sizing: border-box !important;
                display: block !important;
                margin: 0 !important;
                opacity: 1 !important;
                padding: 20px 24px !important;
                visibility: visible !important;
            }

            #smpm-access-denied-card h1,
            #smpm-access-denied-card h2,
            #smpm-access-denied-card h3 {
                margin-top: 0 !important;
            }

            #smpm-access-denied-card p {
                font-size: 14px;
                line-height: 1.6;
            }

            #smpm-access-denied-card
            .smpm-access-denied-actions {
                margin-bottom: 0 !important;
                margin-top: 20px !important;
            }

            @media screen and (max-width: 782px) {
                #smpm-access-denied-page {
                    margin: 15px 10px 0 !important;
                }
            }
        </style>

        <script id="smpm-access-denied-script">
            (function () {
                "use strict";

                function showAccessDeniedPage() {
                    var page = document.getElementById(
                        "smpm-access-denied-page"
                    );

                    if (!page) {
                        return;
                    }

                    var wpBody = document.getElementById(
                        "wpbody"
                    );

                    var wpBodyContent = document.getElementById(
                        "wpbody-content"
                    );

                    if (wpBody) {
                        wpBody.style.setProperty(
                            "display",
                            "block",
                            "important"
                        );

                        wpBody.style.setProperty(
                            "visibility",
                            "visible",
                            "important"
                        );

                        wpBody.style.setProperty(
                            "opacity",
                            "1",
                            "important"
                        );
                    }

                    if (wpBodyContent) {
                        wpBodyContent.style.setProperty(
                            "display",
                            "block",
                            "important"
                        );

                        wpBodyContent.style.setProperty(
                            "visibility",
                            "visible",
                            "important"
                        );

                        wpBodyContent.style.setProperty(
                            "opacity",
                            "1",
                            "important"
                        );
                    }

                    page.style.setProperty(
                        "display",
                        "block",
                        "important"
                    );

                    page.style.setProperty(
                        "visibility",
                        "visible",
                        "important"
                    );

                    page.style.setProperty(
                        "opacity",
                        "1",
                        "important"
                    );
                }

                if (
                    document.readyState === "loading"
                ) {
                    document.addEventListener(
                        "DOMContentLoaded",
                        showAccessDeniedPage
                    );
                } else {
                    showAccessDeniedPage();
                }

                /*
                 * Executa novamente após os scripts do rodapé.
                 * Isso impede que uma rotina tardia deixe a
                 * página branca.
                 */
                window.addEventListener(
                    "load",
                    showAccessDeniedPage
                );

                window.setTimeout(
                    showAccessDeniedPage,
                    100
                );

                window.setTimeout(
                    showAccessDeniedPage,
                    500
                );
            }());
        </script>
        <?php
    }
"""


def update_main_file() -> bool:
    """Atualiza Version e SMPM_VERSION."""

    content = read_text(MAIN_FILE)
    original_content = content

    version_header = re.compile(
        r"(?m)^(\s*\*\s*Version:\s*)[^\r\n]+$"
    )

    version_constant = re.compile(
        r"define\(\s*'SMPM_VERSION'\s*,\s*"
        r"'[^']+'\s*\);"
    )

    if version_header.search(content) is None:
        raise UpdateError(
            "Cabeçalho Version não encontrado em "
            f"{MAIN_FILE.name}."
        )

    if version_constant.search(content) is None:
        raise UpdateError(
            "Constante SMPM_VERSION não encontrada em "
            f"{MAIN_FILE.name}."
        )

    content = version_header.sub(
        rf"\g<1>{TARGET_VERSION}",
        content,
        count=1,
    )

    content = version_constant.sub(
        (
            "define( 'SMPM_VERSION', "
            f"'{TARGET_VERSION}' );"
        ),
        content,
        count=1,
    )

    if content == original_content:
        return False

    write_text(MAIN_FILE, content)

    return True


def update_core_file() -> bool:
    """
    Valida os hooks existentes e mantém a versão
    centralizada em SMPM_VERSION.
    """

    content = read_text(CORE_FILE)
    original_content = content

    required_callbacks = (
        "smpm_custom_dashboard_content",
        "smpm_add_access_denied_page",
        "smpm_block_direct_access",
    )

    missing_callbacks = [
        callback
        for callback in required_callbacks
        if callback not in content
    ]

    if missing_callbacks:
        raise UpdateError(
            "Os seguintes callbacks obrigatórios não "
            "foram encontrados em "
            f"{CORE_FILE.name}:\n- "
            + "\n- ".join(missing_callbacks)
        )

    content = re.sub(
        r"\$this->version\s*=\s*'[^']+';",
        "$this->version = SMPM_VERSION;",
        content,
        count=1,
    )

    if content == original_content:
        return False

    write_text(CORE_FILE, content)

    return True


def update_admin_file() -> bool:
    """Corrige a renderização das páginas administrativas."""

    content = read_text(ADMIN_FILE)
    original_content = content

    content, dashboard_changed = replace_php_method(
        content,
        "smpm_custom_dashboard_content",
        CUSTOM_DASHBOARD_CONTENT,
    )

    if dashboard_changed:
        print(
            "  [ALTERADO] "
            "smpm_custom_dashboard_content()"
        )

    content, access_page_changed = replace_php_method(
        content,
        "smpm_render_access_denied_page",
        RENDER_ACCESS_DENIED_PAGE,
    )

    if access_page_changed:
        print(
            "  [ALTERADO] "
            "smpm_render_access_denied_page()"
        )

    if content == original_content:
        return False

    write_text(ADMIN_FILE, content)

    return True


def update_readme() -> bool:
    """Atualiza Stable tag e changelog."""

    content = read_text(README_FILE)
    original_content = content
    newline = get_newline(content)

    stable_tag = re.compile(
        r"(?m)^Stable tag:\s*[^\r\n]+$"
    )

    if stable_tag.search(content) is None:
        raise UpdateError(
            "Stable tag não encontrada em readme.txt."
        )

    content = stable_tag.sub(
        f"Stable tag: {TARGET_VERSION}",
        content,
        count=1,
    )

    changelog = normalize_block(
        """
= 17.14.5 =
* Corrigida a página de Acesso não autorizado que desaparecia após o carregamento.
* Removida a classe genérica wrap da página de acesso negado.
* Adicionado um contêiner exclusivo para a página de acesso negado.
* Limitada a personalização do Painel exclusivamente à tela dashboard.
* Removida a ocultação genérica dos elementos .wrap.
* Mantida a ocultação apenas dos componentes nativos do Painel.
* Reforçada a exibição da mensagem de acesso negado após o carregamento.
* Preservada a mensagem configurável pelos administradores.
* Preservados todos os bloqueios e redirecionamentos da versão 17.14.4.
""",
        newline,
    )

    if "= 17.14.5 =" not in content:
        changelog_header = (
            f"== Changelog =={newline}"
        )

        if changelog_header not in content:
            raise UpdateError(
                "Seção Changelog não encontrada "
                "em readme.txt."
            )

        content = content.replace(
            changelog_header,
            (
                changelog_header
                + changelog
                + newline
                + newline
            ),
            1,
        )

    if content == original_content:
        return False

    write_text(README_FILE, content)

    return True


def run_python_syntax_check() -> None:
    """Valida a sintaxe do atualizador."""

    script_path = Path(__file__).resolve()

    result = subprocess.run(
        [
            sys.executable,
            "-m",
            "py_compile",
            str(script_path),
        ],
        capture_output=True,
        text=True,
        check=False,
    )

    if result.returncode != 0:
        output = (
            result.stderr
            or result.stdout
        ).strip()

        raise UpdateError(
            "Erro de sintaxe no atualizador:\n"
            f"{output}"
        )

    pycache_directory = (
        script_path.parent
        / "__pycache__"
    )

    if pycache_directory.is_dir():
        shutil.rmtree(
            pycache_directory,
            ignore_errors=True,
        )

    print(
        "  [OK] Sintaxe Python validada."
    )


def run_php_lint() -> None:
    """Valida a sintaxe PHP quando disponível."""

    php_executable = shutil.which("php")

    if php_executable is None:
        print(
            "\n[AVISO] PHP CLI não encontrado. "
            "A validação PHP foi ignorada."
        )
        return

    print("\nValidando sintaxe PHP...")

    for php_file in (
        MAIN_FILE,
        CORE_FILE,
        ADMIN_FILE,
    ):
        result = subprocess.run(
            [
                php_executable,
                "-l",
                str(php_file),
            ],
            capture_output=True,
            text=True,
            check=False,
        )

        if result.returncode != 0:
            output = (
                result.stdout
                or result.stderr
            ).strip()

            raise UpdateError(
                "Erro de sintaxe em "
                f"'{php_file}':\n{output}"
            )

        print(
            "  [OK] "
            f"{php_file.relative_to(PLUGIN_DIR)}"
        )


def exclude_from_zip(
    relative_path: Path,
) -> bool:
    """Define os arquivos excluídos do pacote."""

    excluded_directories = {
        ".git",
        ".github",
        ".idea",
        ".vscode",
        "__pycache__",
        "node_modules",
    }

    excluded_files = {
        ".DS_Store",
        "Thumbs.db",
    }

    excluded_suffixes = {
        ".pyc",
        ".pyo",
    }

    if any(
        part in excluded_directories
        for part in relative_path.parts
    ):
        return True

    if relative_path.name in excluded_files:
        return True

    return (
        relative_path.suffix.lower()
        in excluded_suffixes
    )


def remove_old_packages() -> None:
    """Remove os pacotes GFE anteriores."""

    for old_package in ROOT_DIR.glob(
        "gfe-*.zip"
    ):
        if old_package == ZIP_FILE:
            continue

        try:
            old_package.unlink()
        except OSError as exc:
            raise UpdateError(
                "Não foi possível remover o pacote "
                "anterior:\n"
                f"{old_package}\n"
                "Verifique se o arquivo está aberto."
            ) from exc


def create_zip() -> Path:
    """Gera o pacote ZIP completo do plugin."""

    remove_old_packages()

    if ZIP_FILE.exists():
        try:
            ZIP_FILE.unlink()
        except OSError as exc:
            raise UpdateError(
                "Não foi possível substituir o pacote:\n"
                f"{ZIP_FILE}\n"
                "Verifique se ele está aberto."
            ) from exc

    print("\nCompactando o plugin...")

    file_count = 0

    try:
        with zipfile.ZipFile(
            ZIP_FILE,
            mode="w",
            compression=zipfile.ZIP_DEFLATED,
            compresslevel=9,
        ) as archive:
            for file_path in sorted(
                PLUGIN_DIR.rglob("*")
            ):
                if not file_path.is_file():
                    continue

                relative_path = (
                    file_path.relative_to(
                        PLUGIN_DIR
                    )
                )

                if exclude_from_zip(
                    relative_path
                ):
                    continue

                archive_name = (
                    Path(PLUGIN_DIR.name)
                    / relative_path
                )

                archive.write(
                    file_path,
                    archive_name.as_posix(),
                )

                file_count += 1

    except OSError as exc:
        ZIP_FILE.unlink(
            missing_ok=True,
        )

        raise UpdateError(
            f"Falha ao gerar o ZIP: {exc}"
        ) from exc

    if (
        file_count <= 0
        or not ZIP_FILE.is_file()
        or ZIP_FILE.stat().st_size <= 0
    ):
        ZIP_FILE.unlink(
            missing_ok=True,
        )

        raise UpdateError(
            "O pacote ZIP não foi criado corretamente."
        )

    print(
        f"  [OK] {file_count} arquivos compactados."
    )

    return ZIP_FILE


def main() -> int:
    """Executa a atualização completa."""

    try:
        print("=" * 68)
        print(
            "Atualizador GFE/SMPM — "
            f"{TARGET_VERSION}"
        )
        print("=" * 68)
        print(f"Base local: {PLUGIN_DIR}")
        print("Versão esperada: 17.14.4")
        print("Backup automático: desativado")

        print("\nExecutando validações...")

        run_python_syntax_check()
        validate_files()
        validate_base_version()

        print("\nAplicando alterações...")

        results = {
            MAIN_FILE: update_main_file(),
            CORE_FILE: update_core_file(),
            ADMIN_FILE: update_admin_file(),
            README_FILE: update_readme(),
        }

        run_php_lint()

        package_path = create_zip()

        print("\n" + "=" * 68)
        print("Atualização concluída com sucesso.")
        print(f"Versão: {TARGET_VERSION}")

        for path, changed in results.items():
            status = (
                "ALTERADO"
                if changed
                else "JÁ ATUALIZADO"
            )

            print(
                f"  [{status}] "
                f"{path.relative_to(PLUGIN_DIR)}"
            )

        print(
            f"\nPacote criado: {package_path}"
        )
        print("=" * 68)

        return 0

    except UpdateError as exc:
        print(
            f"\n[ERRO] {exc}",
            file=sys.stderr,
        )

        return 1

    except PermissionError as exc:
        print(
            f"\n[ERRO] Acesso negado: {exc}",
            file=sys.stderr,
        )

        return 1

    except KeyboardInterrupt:
        print(
            "\nOperação cancelada.",
            file=sys.stderr,
        )

        return 130

    except Exception as exc:
        print(
            "\n[ERRO INESPERADO] "
            f"{type(exc).__name__}: {exc}",
            file=sys.stderr,
        )

        return 1


if __name__ == "__main__":
    raise SystemExit(main())
