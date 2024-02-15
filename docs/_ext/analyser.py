# Copyright (c) Vaimo Group. All rights reserved.
# See LICENSE_VAIMO.txt for license details.

import os
import sys

sys.path.append(os.path.abspath('./'))

from utils import *

def resolve_package_details(package_root):
    repository_clone_url = get_context_value(['GIT_URL', 'REPOSITORY_URL'], ['git', 'config', '--get', 'remote.origin.url']).strip()
    repository_url = normalize_url(repository_clone_url)
    
    repository_name = u'/'.join(repository_url.split('/')[-2:]).rstrip()
    package_name = ''
    
    readme_path = '%s/%s' % (package_root, 'README.md')
    
    package_config = load_package_config(package_root)

    if not package_config:
        module_code = '/'.join(repository_url.split('/')[-2:]).rstrip()
        module_vendor = module_code.split('/')[0]
        module_description = 'Documentation for %s' % module_code
        module_issues_link = '%s/issues' % normalize_url(repository_url)
        package_name = repository_name
    else:
        if 'name' in package_config:
            package_name = package_config['name']    
    
        module_description = package_config['description']    
    
        if 'support' in package_config:
            module_issues_link = package_config['support']['issues']
        else:
            module_issues_link = '%s/issues' % normalize_url(repository_url)
    
        registration_file_path = resolve_registration_file_path(package_config, package_root)

        if registration_file_path:
            module_code = get_magento_module_code(registration_file_path)
            module_vendor = module_code.split('_')[0]
        elif os.path.isfile(readme_path):
            readme_title = get_process_output(['head', '-n1', readme_path]).strip()
            module_code = readme_title.replace('#', ' ').strip().split(' ')[0]
            module_vendor = module_code.split('_')[0]

    branch_name = get_context_value('JOB_BASE_NAME', ['git', 'rev-parse', '--abbrev-ref', 'HEAD']).strip()
    build_changeset = get_process_output(['git', 'rev-parse', 'HEAD']).strip()

    composer_executable = os.getenv('COMPOSER_EXECUTABLE')
    version = get_process_output([composer_executable, '--working-dir=./%s' % package_root, 'changelog:version', '--branch', branch_name]).strip()

    return {
        'code': module_code,
        'version': version,
        'vendor': module_vendor,
        'description': module_description,
        'issues_url': module_issues_link,
        'clone_url': repository_clone_url,
        'url': repository_url,
        'branch': branch_name,
        'changeset': build_changeset,
        'repository': repository_name,
        'name': package_name
    }
