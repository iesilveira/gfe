=== Gestor de Funções para Editores - SES/MG ===
Contributors: manus
Tags: permissions, users, roles, editor, access-control
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 17.13
Requires PHP: 7.4
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Restringe e personaliza o acesso de usuários com função de Editor no WordPress, permitindo controle granular sobre páginas, categorias e plugins.

== Description ==

O plugin Gestor de Funções para Editores - SES/MG foi desenvolvido especificamente para a Secretaria de Estado de Saúde de Minas Gerais, permitindo um controle granular das permissões de usuários com função de Editor.

= Características principais =

* Controle de acesso a páginas específicas
* Controle de acesso a categorias de posts
* Controle de acesso a plugins selecionados
* Interface intuitiva integrada ao painel de usuários do WordPress
* Funcionalidade de busca para facilitar a seleção de itens
* Botões "Marcar todos" e "Desmarcar todos" para facilitar a configuração
* Aplicação de restrições apenas para usuários com função de Editor
* Administradores mantêm acesso total e irrestrito

= Como usar =

1. Instale e ative o plugin
2. Vá para Usuários > Todos os usuários
3. Edite um usuário com função de Editor
4. Encontre a seção "Gerenciamento de Permissões SES/MG"
5. Clique em "Gerenciar permissões"
6. Configure as permissões desejadas para páginas, categorias e plugins
7. Salve as alterações

= Segurança =

O plugin foi desenvolvido seguindo as melhores práticas de segurança do WordPress:
* Validação e sanitização de todos os dados de entrada
* Verificação de nonces para prevenir ataques CSRF
* Verificação de capacidades de usuário antes de permitir alterações
* Escape de saída para prevenir ataques XSS

== Installation ==

1. Faça upload dos arquivos do plugin para o diretório `/wp-content/plugins/saude-mg-permission-manager/`
2. Ative o plugin através do menu 'Plugins' no WordPress
3. O plugin estará pronto para uso imediatamente

== Frequently Asked Questions ==

= O plugin afeta usuários Administradores? =

Não. O plugin aplica restrições apenas a usuários com função de Editor. Administradores mantêm acesso total e irrestrito.

= É possível configurar permissões para outros tipos de usuário? =

Atualmente, o plugin foi desenvolvido especificamente para usuários com função de Editor, conforme especificação da SES/MG.

= O plugin é compatível com outros plugins de gerenciamento de usuários? =

O plugin foi desenvolvido para integrar-se perfeitamente com a lógica nativa de roles do WordPress e evitar conflitos com outros plugins.

== Screenshots ==

1. Interface de gerenciamento de permissões
2. Botão "Gerenciar permissões" na tela de edição de usuário
3. Modal com controles de páginas, categorias e plugins

== Changelog ==

= 17.13 =
* Corrigida a identificação de páginas abertas por post.php.
* Corrigido o acesso de Editores às páginas autorizadas.
* Corrigida a compatibilidade da validação com o Elementor.
* Separada corretamente a validação de páginas e posts.
* Corrigida a normalização dos IDs armazenados nas permissões.
* Corrigida a liberação das capacidades primitivas exigidas pelo WordPress.


= 17.12 =
* Restaurado o item wp-admin-bar-site-name na barra superior.
* Restaurado o nome do site com link para a página pública.
* Mantidos o nome do usuário sem avatar e somente a opção Sair.


= 17.11 =
* Removida a logo personalizada da barra lateral.
* Removido o preload do favicon usado no menu administrativo.
* Removidos os ajustes de tamanho e posicionamento da barra lateral.
* Removidos os estilos e scripts relacionados à identidade visual lateral.
* Preservadas as restrições funcionais do menu do Editor.


= 17.10 =
* Carregado o CSS crítico junto ao stylesheet nativo wp-admin.
* Eliminada a aplicação tardia do layout na página Painel.
* Reservado imediatamente o espaço de 80x80 do ícone lateral.
* Removidos os estilos críticos impressos por hooks tardios.
* Corrigido o efeito de exibição momentânea do layout original.


= 17.9 =
* Movidos os estilos críticos do menu lateral para o cabeçalho administrativo.
* Adicionado preload prioritário do favicon do site.
* Eliminado o atraso visual na aplicação do favicon e do layout do Editor.
* Removida a impressão tardia dos estilos durante a montagem do menu.


= 17.8 =
* Removido definitivamente o avatar ao lado do nome do usuário.
* Adicionada proteção contra avatares reinseridos por outros plugins.
* Mantidos somente o nome do usuário e a opção Sair.


= 17.7 =
* Removido o avatar da barra superior.
* Removido o avatar do submenu do usuário.
* Removida a opção de editar perfil.
* Mantidos somente o nome do usuário e a opção Sair.


= 17.6 =
* Restaurado o nome e o avatar do usuário no canto superior direito.
* Preservado o grupo nativo top-secondary da barra administrativa.
* Mantido o menu nativo de usuário do WordPress.


= 17.5 =
* Restaurado o menu nativo do usuário na barra superior do WordPress.
* Definido z-index 999 para a barra superior.
* Ajustado o padding direito do Painel para 20px.
* Ajustada a posição fixa do menu lateral em telas sticky-menu.
* Corrigido o filtro Todos e o filtro por categoria usando restrição direta na consulta SQL.


= 17.4 =
* Movido o favicon dinâmico para a barra lateral.
* Ajustado o favicon para 80x80 e centralizado.
* Exibido o nome do usuário à direita na barra superior, sem link.
* Removida a opção Gerar com a IA do Elementor da Mídia.
* Corrigido o filtro Todos e o filtro por categoria dos Posts.
* Ajustadas as margens da página inicial do Painel.


= 17.3 =
* Adicionado o Painel no topo do menu lateral dos Editores.
* Mantidos somente Painel, Posts, Mídia e Páginas no menu lateral.
* Substituída a barra superior pelo favicon dinâmico do site.
* Configurado o favicon para abrir a página pública em uma nova aba.
* Corrigida a formatação dos contadores de Posts e Páginas.
* Corrigido o cálculo dos registros acessíveis em cada situação.
* Corrigido o filtro Todos e a seleção de categorias na listagem de Posts.


= 17.2 =
* Restrito o menu lateral dos Editores a Posts, Mídia e Páginas.
* Restrita a barra superior ao ícone do WordPress e ao nome do site.
* Ocultados os painéis Ajuda e Opções de tela para Editores.
* Corrigidas as contagens das visualizações de Posts e Páginas para considerar somente os registros permitidos.


= 17.1 =
* Corrigido erro crítico na listagem de posts para usuários Editores.
* Removido o uso incompatível do filtro wp_dropdown_cats.
* Corrigido o tratamento de resultados de termos retornados como objetos, inteiros ou strings.
* Reforçada a filtragem das categorias permitidas.


= 1.0.0 =
* Versão inicial do plugin
* Controle de permissões para páginas, categorias e plugins
* Interface de usuário completa com funcionalidades de busca
* Implementação de segurança seguindo padrões WordPress

== Upgrade Notice ==

= 17.1 =
Corrige um erro crítico na listagem de posts de usuários Editores e torna a filtragem de categorias compatível com os diferentes formatos retornados pelo WordPress.


= 1.0.0 =
Versão inicial do plugin.

