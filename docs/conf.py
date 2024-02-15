# Copyright (c) Vaimo Group. All rights reserved.
# See LICENSE_VAIMO.txt for license details.

import sys, os, time
from xml.dom import minidom
from sphinx.highlighting import lexers
from pygments.lexers.web import PhpLexer
from docutils.transforms.references import Substitutions

sys.path.append(os.path.abspath('./_ext'))

from utils import cross_pollinate_variables, merge_dictionaries, render_template
from analyser import resolve_package_details
  
package_details = resolve_package_details('..')

extensions = ['directives']
templates_path = ['_templates']
source_suffix = '.rst'
master_doc = 'index'
project = package_details['code']
copyright = time.strftime("%Y, Vaimo")
version = package_details['version']
release = package_details['version']
exclude_patterns = ['_build', 'includes/*.rst']
pygments_style = 'sphinx'
html_theme = 'default'
html_static_path = ['_static']
htmlhelp_basename = package_details['code'] + 'doc'

latex_elements = {}

code_parts = package_details['code'].split('_')

latex_documents = [
  ('index', package_details['code'] + '.tex', code_parts[0] + '\\_' + code_parts[1] +' Documentation',
   package_details['vendor'], 'manual'),
]

man_pages = [
    ('index', package_details['code'], package_details['code'].replace('_', '\\_'),
     [package_details['vendor']], 1)
]

man_show_urls = False

texinfo_documents = [
  ('index', package_details['code'], package_details['code'] + ' Documentation',
   u'Vaimo', package_details['code'], package_details['description'],
   'Miscellaneous'),
]

html_theme = "sphinx_rtd_theme"
html_theme_path = ["_themes", ]
highlight_language = 'php'

lexers['php'] = PhpLexer(startinline=True)
        
variable_values = {
    'RepositoryUrl' : package_details['clone_url'],
    'RepositoryBase' : package_details['url'],
    'BuildChangeSet' : package_details['changeset'],
    'PackageName': package_details['name'],
    'RepositoryName': package_details['repository'],
    'RepositoryName' : '/'.join(package_details['url'].split('/')[-2:]).rstrip(),
    'RepositorySrcUrl' : '{var:RepositoryBase}/src/{var:BuildChangeSet}',
    'RepositorySrcTipUrl' : '{var:RepositoryBase}/src/%s' % package_details['branch'],
    'RepositoryRawSrcUrl' : '{var:RepositoryBase}/raw/{var:BuildChangeSet}',
    'RepositoryRawSrcTipUrl' : '{var:RepositoryBase}/raw/%s' % package_details['branch'],
    'DocsSrcUrl' : '{var:RepositorySrcUrl}/docs/{var:DocPageName}.rst',
    'DocsSrcTipUrl' : '{var:RepositorySrcTipUrl}/docs/{var:DocPageName}.rst',
    'ChangelogSrcUrl' : '{var:RepositorySrcUrl}/changelog.json',
    'ChangelogSrcTipUrl' : '{var:RepositorySrcTipUrl}/changelog.json',
    'DocumentationLink' : '`{var:RepositoryName} <{var:DocsSrcUrl}>`__',
    'RepositoryLink' : '`{var:RepositoryName} <{var:RepositoryBase}>`__',
    'PackageLink': '`{var:PackageName} <{var:RepositoryBase}>`__',
    'SourcesLink' : '`{var:RepositoryName} <{var:RepositorySrcUrl}>`__',
    'IssuesUrl': package_details['issues_url'],
    'ModuleCode': package_details['code'],
    'ModuleDescription': package_details['description']
}

if not package_details['url']:
    variable_values = reset_dictionary_values(variable_values)

variable_values = cross_pollinate_variables(variable_values)

def replace_source_variables(app, pagename, source):    
    source[0] = render_template(
        source[0], 
        merge_dictionaries(
            app.config.variable_values, 
            {'DocPageName': pagename}
        )
    )

def update_page_context_metadata(app, pagename, templatename, context, doctree):                   
    if 'meta' not in context:
        return

    variables = app.config.variable_values        

    if not variables['RepositoryUrl']:
        return

    var_key = 'DocsSrcTipUrl'
    
    if pagename == 'changelog':
        var_key = 'ChangelogSrcTipUrl'

    context['meta']['bitbucket_url'] = render_template(
        variables.get(var_key, ''), 
        {'DocPageName': pagename}
    )
    
def setup(app):
    app.add_stylesheet('css/custom.css')
    app.add_config_value('variable_values', {}, True)    
    app.connect('source-read', replace_source_variables)   
    app.connect('html-page-context', update_page_context_metadata)
