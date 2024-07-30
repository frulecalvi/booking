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
                            env.APP_ENV = "prod"
                        default:
                            env.APP_ENV = "dev"
                    }

                    env.BUILD_VERSION = "${env.APP_ENV}-${env.GIT_COMMIT_SHORT}"
                    env.BUILD_VERSION_LATEST = "${env.APP_ENV}-latest"

                    env.APP_KEY_CRED = "booking-${env.APP_ENV}-app-key"
                    env.APP_URL_CRED = "booking-${env.APP_ENV}-app-url"
                    env.DB_USERNAME_CRED = "booking-${env.APP_ENV}-db-user"
                    env.DB_PASSWORD_CRED = "booking-${env.APP_ENV}-db-password"
                    env.SESSION_DOMAIN_CRED = "booking-${env.APP_ENV}-session-domain"
                    env.SANCTUM_STATEFUL_DOMAINS_CRED = "booking-${env.APP_ENV}-sanctum-stateful-domains"
                    env.CORS_ALLOWED_ORIGINS_CRED = "booking-${env.APP_ENV}-cors-allowed-origins"
                    env.DOCKER_REPOSITORY_HOST_CRED = "booking-${env.APP_ENV}-docker-repository-host"
                    env.DOCKER_REPOSITORY_USER_CRED = "booking-${env.APP_ENV}-docker-repository-user"
                    env.DOCKER_REPOSITORY_TOKEN_CRED = "booking-${env.APP_ENV}-docker-repository-token"
                }
            }
        }

        stage('Build') {
            steps {
                withCredentials([
                    string(credentialsId: "${APP_KEY_CRED}", variable: 'APP_KEY'),
                    string(credentialsId: "${APP_URL_CRED}", variable: 'APP_URL'),
                    string(credentialsId: "${DB_USERNAME_CRED}", variable: 'DB_USERNAME'),
                    string(credentialsId: "${DB_PASSWORD_CRED}", variable: 'DB_PASSWORD'),
                    string(credentialsId: "${SESSION_DOMAIN_CRED}", variable: 'SESSION_DOMAIN'),
                    string(credentialsId: "${SANCTUM_STATEFUL_DOMAINS_CRED}", variable: 'SANCTUM_STATEFUL_DOMAINS'),
                    string(credentialsId: "${CORS_ALLOWED_ORIGINS_CRED}", variable: 'CORS_ALLOWED_ORIGINS'),
                    string(credentialsId: "${DOCKER_REPOSITORY_HOST_CRED}", variable: 'DOCKER_REPOSITORY_HOST'),
                    string(credentialsId: "${DOCKER_REPOSITORY_USER_CRED}", variable: 'DOCKER_REPOSITORY_USER'),
                ]) {
                    sh '''#!/bin/bash

                        cp .env.example .env

                        sed -i "s/APP_ENV=.*/APP_ENV=$APP_ENV/g" .env
                        sed -i "s/APP_ENV=.*/APP_KEY=$APP_KEY/g" .env
                        sed -i "s/APP_URL=.*/APP_URL=${APP_URL//\\//\\\\\\/}/g" .env
                        sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USERNAME/g" .env
                        sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/g" .env
                        sed -i "s/SESSION_DOMAIN=.*/SESSION_DOMAIN=$SESSION_DOMAIN/g" .env
                        sed -i "s/SANCTUM_STATEFUL_DOMAINS=.*/SANCTUM_STATEFUL_DOMAINS=$SANCTUM_STATEFUL_DOMAINS/g" .env
                        sed -i "s/CORS_ALLOWED_ORIGINS=.*/CORS_ALLOWED_ORIGINS=$CORS_ALLOWED_ORIGINS/g" .env

                        echo BUILD_VERSION=$BUILD_VERSION >> .env
                        echo DOCKER_REPOSITORY_HOST=$DOCKER_REPOSITORY_HOST >> .env
                        echo DOCKER_REPOSITORY_USER=$DOCKER_REPOSITORY_USER >> .env
                    '''

                    sh 'docker compose build'
                }
            }
        }

        stage('Test') {
            steps {
                withCredentials([
                    string(credentialsId: "${DOCKER_REPOSITORY_HOST_CRED}", variable: 'DOCKER_REPOSITORY_HOST'),
                    string(credentialsId: "${DOCKER_REPOSITORY_USER_CRED}", variable: 'DOCKER_REPOSITORY_USER'),
                ]) {
                    sh 'docker run $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-app:$BUILD_VERSION php ./vendor/phpunit/phpunit/phpunit tests/Feature'
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
                    
                    sh 'docker image tag $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-app:$BUILD_VERSION $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-app:$BUILD_VERSION_LATEST'
                    sh 'docker image tag $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-webserver:$BUILD_VERSION $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-webserver:$BUILD_VERSION_LATEST'
                    sh 'docker image tag $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-db:$BUILD_VERSION $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-db:$BUILD_VERSION_LATEST'

                    sh 'docker image push $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-app:$BUILD_VERSION'
                    sh 'docker image push $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-webserver:$BUILD_VERSION'
                    sh 'docker image push $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-db:$BUILD_VERSION'

                    sh 'docker image push $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-app:$BUILD_VERSION_LATEST'
                    sh 'docker image push $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-webserver:$BUILD_VERSION_LATEST'
                    sh 'docker image push $DOCKER_REPOSITORY_HOST/$DOCKER_REPOSITORY_USER/booking-db:$BUILD_VERSION_LATEST'
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
                    sh 'docker compose pull'
                    sh 'docker compose up -d'
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
