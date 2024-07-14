pipeline {
    agent any
    
    stages {
        stage('Prepare') {
            steps {
                script {
                    env.GIT_COMMIT_SHORT = "${sh(script: 'git rev-parse --short HEAD', returnStdout: true).trim()}"
                    // env.GIT_COMMIT_SHORT = "4e6af51"

                    switch (env.GIT_BRANCH) {
                        case 'origin/main':
                            env.APP_URL_CRED = "booking-prod-app-url"
                            env.DB_USERNAME_CRED = "booking-prod-db-user"
                            env.DB_PASSWORD_CRED = "booking-prod-db-password"
                            env.DOCKER_REPOSITORY_HOST_CRED = "booking-prod-docker-repository-host"
                            env.DOCKER_REPOSITORY_USER_CRED = "booking-prod-docker-repository-user"
                            env.DOCKER_REPOSITORY_TOKEN_CRED = "booking-prod-docker-repository-token"
                        default:
                            env.APP_URL_CRED = "booking-dev-app-url"
                            env.DB_USERNAME_CRED = "booking-dev-db-user"
                            env.DB_PASSWORD_CRED = "booking-dev-db-password"
                            env.DOCKER_REPOSITORY_HOST_CRED = "booking-dev-docker-repository-host"
                            env.DOCKER_REPOSITORY_USER_CRED = "booking-dev-docker-repository-user"
                            env.DOCKER_REPOSITORY_TOKEN_CRED = "booking-dev-docker-repository-token"
                    }
                }
            }
        }

        stage('Build') {
            steps {
                withCredentials([
                    string(credentialsId: "${APP_URL_CRED}", variable: 'APP_URL'),
                    string(credentialsId: "${DB_USERNAME_CRED}", variable: 'DB_USERNAME'),
                    string(credentialsId: "${DB_PASSWORD_CRED}", variable: 'DB_PASSWORD'),
                    string(credentialsId: "${DOCKER_REPOSITORY_HOST_CRED}", variable: 'DOCKER_REPOSITORY_HOST'),
                    string(credentialsId: "${DOCKER_REPOSITORY_USER_CRED}", variable: 'DOCKER_REPOSITORY_USER'),
                ]) {
                    sh '''#!/bin/bash

                        cp .env.example .env

                        sed -i "s/APP_URL=.*/APP_URL=${APP_URL//\\//\\\\\\/}/g" .env
                        sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USERNAME/g" .env
                        sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/g" .env

                        echo GIT_COMMIT_SHORT=$GIT_COMMIT_SHORT >> .env
                        echo DOCKER_REPOSITORY_HOST=$DOCKER_REPOSITORY_HOST >> .env
                        echo DOCKER_REPOSITORY_USER=$DOCKER_REPOSITORY_USER >> .env
                    '''

                    sh 'docker-compose build'
                }
            }
        }

        stage('Test') {
            steps {
                withCredentials([
                    string(credentialsId: "${DOCKER_REPOSITORY_HOST_CRED}", variable: 'DOCKER_REPOSITORY_HOST'),
                    string(credentialsId: "${DOCKER_REPOSITORY_USER_CRED}", variable: 'DOCKER_REPOSITORY_USER'),
                ]) {
                    sh 'docker run $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-app:$GIT_COMMIT_SHORT php ./vendor/phpunit/phpunit/phpunit tests/Feature'
                }
            }
        }
        
        stage('Push Docker Images') {
            steps {
                withCredentials([
                    string(credentialsId: "${DOCKER_REPOSITORY_HOST_CRED}", variable: 'DOCKER_REPOSITORY_HOST'),
                    string(credentialsId: "${DOCKER_REPOSITORY_USER_CRED}", variable: 'DOCKER_REPOSITORY_USER'),
                    string(credentialsId: "${DOCKER_REPOSITORY_TOKEN_CRED}", variable: 'DOCKER_REPOSITORY_TOKEN'),
                ]) {
                    sh 'echo "$DOCKER_REPOSITORY_TOKEN" | docker login $DOCKER_REPOSITORY_HOST -u $DOCKER_REPOSITORY_USER --password-stdin'
                    sh 'docker image push $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-app:$GIT_COMMIT_SHORT'
                    sh 'docker image push $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-webserver:$GIT_COMMIT_SHORT'
                    sh 'docker image push $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-db:$GIT_COMMIT_SHORT'
                }
            }
        }
        
        stage('Deploy') {
            steps {
                withCredentials([
                    string(credentialsId: "${DOCKER_REPOSITORY_HOST_CRED}", variable: 'DOCKER_REPOSITORY_HOST'),
                    string(credentialsId: "${DOCKER_REPOSITORY_USER_CRED}", variable: 'DOCKER_REPOSITORY_USER'),
                    string(credentialsId: "${DOCKER_REPOSITORY_TOKEN_CRED}", variable: 'DOCKER_REPOSITORY_TOKEN'),
                ]) {
                    sh 'echo "$DOCKER_REPOSITORY_TOKEN" | docker login $DOCKER_REPOSITORY_HOST -u $DOCKER_REPOSITORY_USER --password-stdin'
                    sh 'docker-compose pull'
                    sh 'docker-compose up -d'
                }
            }
        }
        
        stage('Clean') {
            steps {
                sh 'rm .env'
            }
        }
    }
}
