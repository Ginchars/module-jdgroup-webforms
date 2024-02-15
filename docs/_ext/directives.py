# Copyright (c) Vaimo Group. All rights reserved.
# See LICENSE_VAIMO.txt for license details.

from sphinx.directives.other import Include
from docutils import statemachine
from utils import render_template

class MutableInclude(Include):
    def run(self, *args, **kwargs):
        if self.arguments[0].startswith('<') and self.arguments[0].endswith('>'):
            return super(MutableInclude, self).run(*args, **kwargs)
                
        old_literal = None
        
        if 'literal' in self.options:
            old_literal = self.options['literal'] 
        
        self.options['literal'] = True

        app = self.state.document.settings.env.app
        
        try:
            literal_block = super(MutableInclude, self).run(*args, **kwargs)
        finally:
            if old_literal:
                self.options['literal'] = old_literal
            else:
                del self.options['literal']

        raw_content = literal_block[0].rawsource
        
        variables = app.config.variable_values

        if not variables['RepositoryUrl']:
            return []

        raw_content = render_template(raw_content, variables)
         
        content_lines = statemachine.string2lines(
            raw_content, 
            self.options.get('tab-width', self.state.document.settings.tab_width), 
            convert_whitespace=True
        )
        
        self.state_machine.insert_input(content_lines, self.arguments[0])
        
        return []
        
def setup(app):
    app.add_directive('include', MutableInclude)
