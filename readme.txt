=== Gestor de Funções para Editores - SES/MG ===
Contributors: manus
Tags: permissions, users, roles, editor, access-control
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
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

= 1.0.0 =
* Versão inicial do plugin
* Controle de permissões para páginas, categorias e plugins
* Interface de usuário completa com funcionalidades de busca
* Implementação de segurança seguindo padrões WordPress

== Upgrade Notice ==

= 1.0.0 =
Versão inicial do plugin.

