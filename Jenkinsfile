#!groovy
// Copyright © Vaimo Group. All rights reserved.
// See LICENSE_VAIMO.txt for license details.

// This pipeline is meant to be used together with vaimo/composer-changelogs (>=0.10.0) and is build on an
// assumption that the CI job name matches with package name (required for package generation).

@Library('platform-jenkins-pipeline') _

String stripWarnings(String input) {
    return input.replaceAll("^Warning: .*?\\n", "");
}

pipeline {
    agent { label 'php81' }
    options {
        buildDiscarder(logRotator(numToKeepStr: '5'))
        disableConcurrentBuilds()
        timeout(time: 20, unit: 'MINUTES')
        timestamps()
    }
    stages {
        stage('Collect Info') {
            steps {
                script {
                    env.CHANGELOG_URL = ''
                    env.SHOULD_RELEASE = '0'
                    env.SHOULD_ANALYSE = '0'
                    env.SHOULD_TEST = '0'
                    env.BRANCH_NAME = ''
                    env.IS_GIT = true
                    env.SHOULD_USE_COMPOSER_2 = true

                    // Fetching git tags (since Git Plugin runs git fetch with "--no-tags" flag regardless of "Behaviors" specified in config)
                    sh("git fetch --tags ${env.GIT_URL}")

                    def branchName = env.GIT_BRANCH
                    def releaseAuthor = sh(returnStdout: true, script: "git --no-pager show -s --format='%an <%ae>' ${env.GIT_COMMIT}").trim().toLowerCase()
                    env.PACKAGE_NAME = sh(returnStdout: true, script: "cat composer.json|python -c 'import json,sys;o=json.load(sys.stdin);print o[\"name\"]'").trim()
                    env.BRANCH_NAME = env.JOB_BASE_NAME == 'master' ? '' : branchName
                    env.IS_RELEASE_BRANCH = env.JOB_BASE_NAME == 'default' || env.JOB_BASE_NAME == 'master' || env.BRANCH_NAME.startsWith('release/')
                    env.PIPELINE_NAME = sh(returnStdout: true, script: "echo ${env.JOB_NAME}|cut -d'/' -f1").trim()
                    env.REPOSITORY_URL = "${env.GIT_URL}"
                    env.COMMIT_ID = "${env.GIT_COMMIT}"
                    env.REPO_SLUG = new com.vaimo.MultiBranchJob().repoSlug("${env.REPOSITORY_URL}")
                    env.DOCS_FOLDER = 'docs'
                    env.HAS_DOCS = sh(returnStatus: true, script: "test -d ${env.DOCS_FOLDER}") == 0
                    if (!env.HAS_DOCS.toBoolean()) {
                        env.HAS_DOCS = sh(returnStatus: true, script: "mkdir docs && touch docs/.keepfile") == 0
                    }

                    // Obtaining most recent annotated tag on current branch (assuming GIT plugin has already checked out required branch)
                    // "--always" flag in git command below ensures that if there's no single tag yet, git will return commit hash instead of error message
                    def closestAnnotatedTag = sh(returnStdout: true, script: "git describe --tags --abbrev=0 --always").trim()
                    // echo "[GIT module build flow]: closestAnnotatedTag detected: ${closestAnnotatedTag}"
                    // env.SHOULD_BUILD gets "false" if obtained tag (or commit hash) is the same as in env.GIT_COMMIT (zero difference) and "true" otherwise (plus releaseAuthor check)
                    env.SHOULD_BUILD = sh(returnStdout: true, script: "git log ${closestAnnotatedTag}..${env.GIT_COMMIT} --oneline | wc -l").trim() != '0' || releaseAuthor != 'jenkins'

                    env.REPOSITORY_HTTP = sh(returnStdout: true, script: "echo ${env.GIT_URL}|cut -d'@' -f2 | sed 's/:/\\//' | sed 's/.\\{4\\}\$//'").trim()
                    env.REPOSITORY_HTTP = "https://${env.REPOSITORY_HTTP}/src/${env.JOB_BASE_NAME}"
                }
            }
        }
        stage('Find composer') {
            when {
                expression { env.SHOULD_BUILD.toBoolean() == true }
            }
            steps {
                ansiColor('xterm') {
                    script {
                        env.COMPOSER_EXECUTABLE = 'composer'
                        if (env.SHOULD_USE_COMPOSER_2.toBoolean()  == true) {
                            def rc = sh(returnStatus: true, returnStdout: false, script: "composer2 --version")
                            if (rc == 0) {
                                env.COMPOSER_EXECUTABLE = 'composer2'
                            } else {
                                sh('printf "\033[7;49;33m There is no composer2 executable, using composer\033[0m\n"')
                            }
                        }
                        sh("${env.COMPOSER_EXECUTABLE} --version")
                    }
                }
            }
        }
        stage('Start build') {
            when {
                expression { env.SHOULD_BUILD.toBoolean() == true }
            }
            steps {
                ansiColor('xterm') {
                    script {
                        bitbucketStatusNotify(
                             buildState: 'INPROGRESS',
                             buildKey: 'build_package',
                             buildName: 'Build Package',
                             buildDescription: '',
                             repoSlug: "${env.REPO_SLUG}",
                             commitId: "${env.COMMIT_ID}"
                        )
                    }
                }
            }
        }
        stage('Validate') {
            when {
                expression { env.SHOULD_BUILD.toBoolean() == true }
            }
            steps {
                ansiColor('xterm') {
                    script {
                        def validationResult = sh(returnStatus: true, script: "${env.COMPOSER_EXECUTABLE} validate")

                        if (validationResult != 0) {
                            sh("${env.COMPOSER_EXECUTABLE} update --lock --ansi")
                            sh("${env.COMPOSER_EXECUTABLE} validate --ansi")

                            def commitMessage = "composer.lock updated due to changes done to package configuration"
                            sh("git commit -am '${commitMessage}'")
                        }
                    }
                }
            }
        }
        stage('Build Module') {
            when {
                expression { env.SHOULD_BUILD.toBoolean() == true }
            }
            steps {
                ansiColor('xterm') {
                    sh("${env.COMPOSER_EXECUTABLE} install --ansi")
                    sh("${env.COMPOSER_EXECUTABLE} validate --ansi")
                    sh("${env.COMPOSER_EXECUTABLE} changelog:validate --ansi")
                }
                script {
                    env.RELEASE_VERSION = stripWarnings(sh(returnStdout: true, script: "${env.COMPOSER_EXECUTABLE} changelog:version --branch ${env.JOB_BASE_NAME}").trim())
                    env.CHANGELOG_MAJOR_VERSION = stripWarnings(sh(returnStdout: true, script: "${env.COMPOSER_EXECUTABLE} changelog:version --branch ${env.JOB_BASE_NAME} --segments 1").trim())
                    env.SHOULD_RELEASE = env.SHOULD_BUILD.toBoolean() == true && env.CHANGELOG_MAJOR_VERSION != '' && env.IS_RELEASE_BRANCH.toBoolean() == true

                    def isTagFromChangelogMissingInVCS = false
                    // checking if env.RELEASE_VERSION (presumably holding a tag in 100% of cases, otherwise this approach is not reliable) is withing git tags
                    isTagFromChangelogMissingInVCS = sh(returnStdout: true, script: "git tag -l ${env.RELEASE_VERSION}").trim() == ''


                    env.SHOULD_RELEASE = env.SHOULD_RELEASE.toBoolean() && isTagFromChangelogMissingInVCS
                    env.SHOULD_ANALYSE = env.SHOULD_BUILD.toBoolean() == true && sh(returnStatus: true, script: "${env.COMPOSER_EXECUTABLE} list --raw|grep -qw '^code:analyse '") == 0
                    env.SHOULD_TEST = env.SHOULD_BUILD.toBoolean() == true && sh(returnStatus: true, script: "${env.COMPOSER_EXECUTABLE} list --raw|grep -qw '^test '") == 0
                }
            }
        }
        stage('Analyse Code') {
            parallel {
                stage('Assess Code Quality') {
                    when {
                        expression { env.SHOULD_ANALYSE.toBoolean() == true }
                    }
                    steps {
                        bitbucketStatus (key: 'analyse_code', name: 'Code Analysis', repo: "${env.REPOSITORY_URL}", commitId: "${env.COMMIT_ID}") {
                            ansiColor('xterm') {
                                sh("${env.COMPOSER_EXECUTABLE} code:analyse --ansi")
                            }
                        }
                    }
                }
                stage('Run Tests') {
                    when {
                        expression { env.SHOULD_TEST.toBoolean() == true }
                    }
                    steps {
                       bitbucketStatus (key: 'analyse_code', name: 'Code Analysis', repo: "${env.REPOSITORY_URL}", commitId: "${env.COMMIT_ID}") {
                            ansiColor('xterm') {
                                sh("${env.COMPOSER_EXECUTABLE} test --ansi")
                            }
                        }
                    }
                }
            }
        }
        stage('Lock Release') {
            when {
                expression { env.SHOULD_RELEASE.toBoolean() == true }
            }
            steps {
                script {
                    sh("git tag ${env.RELEASE_VERSION}")
                    env.RELEASE_LOCK_HASH = sh(returnStdout: true, script: "git rev-parse HEAD").trim()
                }
            }
        }
        stage('Make Docs') {
            when {
                expression { env.SHOULD_BUILD.toBoolean() == true }
            }
            steps {
                script {
                    env.DOCS_VERSION_SUFFIX = env.CHANGELOG_MAJOR_VERSION != '' ? "--v${env.CHANGELOG_MAJOR_VERSION}" : ''
                    env.DOCS_SUFFIX = env.JOB_BASE_NAME == 'master' ? '' : "${env.DOCS_VERSION_SUFFIX}"
                    env.DOCS_NAME = "${env.PIPELINE_NAME}${env.DOCS_SUFFIX}"
                    env.DOCS_VERSIONED_NAME = "${env.PIPELINE_NAME}${env.DOCS_VERSION_SUFFIX}"

                    if (sh(returnStatus: true, script: "test -f CHANGELOG.md") == 0) {
                        sh("rm CHANGELOG.md")
                    }

                    sh("${env.COMPOSER_EXECUTABLE} changelog:generate --url=${env.REPOSITORY_URL}")

                    env.CHANGELOG_URL = "https://docs.vaimo.com/${env.DOCS_NAME}/changelog.html"

                    env.HAS_CHANGELOG_MD = sh(returnStatus: true, script: "test -f CHANGELOG.md") == 0

                    if (!env.HAS_DOCS.toBoolean() && env.HAS_CHANGELOG_MD.toBoolean() && env.SHOULD_RELEASE.toBoolean()) {
                        env.CHANGELOG_URL = "${env.REPOSITORY_HTTP}/CHANGELOG.md"
                        sh('git add CHANGELOG.md')
                        sh('git commit -m "update CHANGELOG.md output"')
                        env.RELEASE_LOCK_HASH = sh(returnStdout: true, script: "git rev-parse HEAD").trim()
                    }

                    if (env.HAS_DOCS.toBoolean() && sh(returnStatus: true, script: "test -f docs/Makefile") == 0) {
                        env.HAS_DOCS_BUILT = sh(returnStatus: true, script: "cd docs && make clean && make html") == 0
                    }
                }
            }
        }
        stage('Publish') {
            parallel {
                stage('Publish Docs: Development') {
                    when {
                        allOf {
                            expression { env.HAS_DOCS.toBoolean() == true }
                            expression { env.SHOULD_BUILD.toBoolean() == true }
                            expression { env.IS_RELEASE_BRANCH.toBoolean() == true }
                        }
                    }
                    steps {
                        bitbucketStatus (key: 'publish_docs', name: 'Publish Development Docs', repo: "${env.REPOSITORY_URL}", commitId: "${env.COMMIT_ID}") {
                            sh("mc mirror --overwrite --remove --quiet docs/_build/html minio/docs.vaimo.com/${env.DOCS_NAME}--dev")
                            sh("mc mirror --overwrite --remove --quiet docs/_build/html minio/docs.vaimo.com/${env.DOCS_VERSIONED_NAME}--dev")
                        }
                    }
                }

                stage('Publish Docs: Production') {
                    when {
                        allOf {
                            expression { env.HAS_DOCS.toBoolean() == true }
                            expression { env.SHOULD_RELEASE.toBoolean() == true }
                        }
                    }
                    steps {
                        bitbucketStatus (key: 'publish_docs', name: 'Publish Production Docs', repo: "${env.REPOSITORY_URL}", commitId: "${env.COMMIT_ID}") {
                            sh("mc mirror --overwrite --remove --quiet docs/_build/html minio/docs.vaimo.com/${env.DOCS_NAME}")
                            sh("mc mirror --overwrite --remove --quiet docs/_build/html minio/docs.vaimo.com/${env.DOCS_VERSIONED_NAME}")
                        }
                    }
                }

                stage('Publish Release') {
                    when {
                        expression { env.SHOULD_RELEASE.toBoolean() == true }
                    }
                    steps {
                        script {
                            bitbucketStatus (key: 'publish_package', name: 'Publishing Package', repo: "${env.REPOSITORY_URL}", commitId: "${env.COMMIT_ID}") {
                                // Pushing all branches to remote
                                sh("git push ${env.GIT_URL} --all")
                                // Pushing all tags to remote
                                sh("git push ${env.GIT_URL} --tags")
                            }
                        }
                    }
                }
            }
        }
        stage('Publish Package') {
            when {
                expression { env.SHOULD_BUILD.toBoolean() == true }
            }
            steps {
                bitbucketStatus (key: 'publish_package', name: 'Publishing Package', repo: "${env.REPOSITORY_URL}", commitId: "${env.COMMIT_ID}") {
                    generateComposerPackage(moduleName:"${env.REPOSITORY_URL}")
                }
            }
        }
        stage('Announce Release') {
            when {
                expression { env.SHOULD_RELEASE.toBoolean() == true }
            }
            steps {
                script {
                    env.RELEASE_NOTES = stripWarnings(sh(returnStdout: true, script: "${env.COMPOSER_EXECUTABLE} changelog:info --branch ${env.JOB_BASE_NAME} --format slack --brief").trim())
                    env.NOTIFICATION = "*${env.PACKAGE_NAME}*: Release version ${env.RELEASE_VERSION} (<${env.REPOSITORY_HTTP}|src>) (<${env.CHANGELOG_URL}|log>)\n${env.RELEASE_NOTES}"
                }
            }
            post {
                success {
                    slackSend(color: "good", channel: "#jdgm2-modules", message: "${env.NOTIFICATION}")
                }
            }
        }
    }
    post {
        success {
            bitbucketStatusNotify(
                buildState: 'SUCCESSFUL',
                buildKey: 'build_package',
                buildName: 'Build Package',
                buildDescription: '',
                repoSlug: "${env.REPO_SLUG}",
                commitId: "${env.COMMIT_ID}"
            )
        }
                aborted {
                    bitbucketStatusNotify(
                        buildState: 'FAILED',
                        buildKey: 'build_package',
                        buildName: 'Build Aborted',
                        buildDescription: '',
                        repoSlug: "${env.REPO_SLUG}",
                        commitId: "${env.COMMIT_ID}"
                    )
                }
        failure {
            bitbucketStatusNotify(
                buildState: 'FAILED',
                buildKey: 'build_package',
                buildName: 'Build Package',
                buildDescription: '',
                repoSlug: "${env.REPO_SLUG}",
                commitId: "${env.COMMIT_ID}"
            )
        }
        always {
            deleteDir()
            sendNotifications(channel: '#jdgm2-modules', branch: "${env.BRANCH_NAME}")
        }
    }
}
