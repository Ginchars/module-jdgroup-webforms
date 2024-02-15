# Copyright (c) Vaimo Group. All rights reserved.
# See LICENSE_VAIMO.txt for license details.

import os
import subprocess
import json
from collections import OrderedDict

def get_process_output(command_definition):
    try:
        with open(os.devnull, 'w') as FNULL:
            return subprocess.check_output(command_definition, stderr=FNULL)    
    except subprocess.CalledProcessError, e:
        pass
        
    return ''

def get_context_value(env_keys, query_command):
    if type(env_keys) is str:
        env_keys = [env_keys]
    
    for env_key in env_keys:
        value = os.environ.get(env_key, '')
        
        if value:
            return value
            
    if not value:
        value = get_process_output(query_command)

    return value
    
def file_get_contents(filename):
    with open(filename) as f:
        return f.read()
    
def load_package_config(package_root):
    composer_file_path = package_root + '/composer.json'
    
    if not os.path.isfile(composer_file_path):
        return {}
    
    json_decoder = json.JSONDecoder(object_pairs_hook=OrderedDict)
    json_string = file_get_contents(composer_file_path)
    
    return json_decoder.decode(json_string)
    
def resolve_registration_file_path(package_config, package_root):
    if 'autoload' not in package_config or 'psr-4' not in package_config['autoload']:
        return ''

    package_root = os.path.abspath(package_root)

    for key,value in package_config['autoload']['psr-4'].items():
        sources_root = package_root + '/' + value
    
        registration_file = sources_root + '/registration.php'
    
        if not os.path.isfile(registration_file):
            continue

        if os.path.abspath(registration_file).find(package_root) != 0:
            continue 

        return registration_file
        
    return ''

def get_magento_module_code(registration_file_path):
    if not registration_file_path:
        registration_file_path = '<blank>'
    
    if not os.path.isfile(registration_file_path):
        raise Exception('No such file: %s' % registration_file_path)

    registration_code_fetcher = '''    
        namespace Magento\Framework\Component { 
            class ComponentRegistrar { 
                const MODULE=1;const LIBRARY=2;const THEME=3;const LANGUAGE=4; 
                static function register($_, $token) { 
                    echo $token ."\n"; 
                } 
            } 
        }; 
    
        namespace {
            include "{var:RegistrationFile}";
        };
    '''
    
    registration_query = render_template(registration_code_fetcher, {
        'RegistrationFile': registration_file_path
    })
    
    output = get_process_output(['php', '-r', registration_query]).strip()
    
    return next(iter(output.splitlines()), None)

def render_template(template, variables):
    result = template
    
    for name,value in variables.items():
        if not value:
            value = ''

        result = result.replace('{var:%s}' % name, value)
            
    return result
    
def normalize_url(url): 
    if url[0:4] == 'http':
        return url

    if url.endswith('.git'):
        url = url[:-4]

    return 'https://%s' % '@'.join(url.replace('.org:', '.org/').split('@')[1:]).rstrip()

def merge_dictionaries(dict1, dict2):
    result = dict1.copy()
    
    result.update(dict2)
    
    return result
    
def cross_pollinate_variables(variables):
    result = variables.copy()
    
    for name in result:
        result[name] = render_template(result[name], result)

    return result

def reset_dictionary_values(data):
    for key in data:
        data[key] = ''
        
    return data
