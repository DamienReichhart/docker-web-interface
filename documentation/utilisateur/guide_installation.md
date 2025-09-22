# Guide d'Installation

**Auteur**: REICHHART Damien - BENAD Kilian  
**Date de création**: 23/04/2025
**Dernière modification**: 23/04/2025

## Sommaire

1. [Introduction](#introduction)
2. [Prérequis](#prérequis)
3. [Installation](#installation)
4. [Configuration](#configuration)
5. [Vérification](#vérification)
6. [Dépannage](#dépannage)

## Introduction

Ce guide décrit les étapes d'installation de l'application de gestion de conteneurs Docker. Suivez attentivement chaque étape pour une installation réussie.

## Prérequis

### Système d'Exploitation
- Linux (Ubuntu 20.04 LTS ou supérieur recommandé)
- Windows 10/11 avec WSL2
- macOS 10.15 ou supérieur

### Logiciels Requis
- Docker Engine 20.10 ou supérieur
- Docker Compose 2.0 ou supérieur
- Git
- PHP 8.1 ou supérieur
- Composer 2.0 ou supérieur
- MariaDB 10.6 ou supérieur

### Ressources Système
- CPU : 2 cœurs minimum
- RAM : 4 Go minimum
- Stockage : 1 Go d'espace libre

## Installation


### 1. Installation de l'Application

```bash
make up
```
### 2. Préparation de l'Environnement

Installation des dépendances php :

```bash
make composer-install
```

Initialisation de la base de donnée

```bash
make migrate
```

## Configuration

Éditer le fichier `.env` avec les valeurs appropriées :
```env


### Database ###
MYSQL_ROOT_PASSWORD = root
MYSQL_DATABASE = AtelierPro
MYSQL_USER = dev
MYSQL_PASSWORD = MotDePasseSecurise
MYSQL_HOST = 'unix_socket=/var/run/mysqld/mysqld.sock'

#MYSQL_HOST = 'host=192.168.20.30'

MYSQL_POST = 3306

### Apps settings ###
DB_CONNECTION = mysql
DB_HOST = ${MYSQL_HOST}
DB_DATABASE = ${MYSQL_DATABASE}
DB_USERNAME = ${MYSQL_USER}
DB_PASSWORD = ${MYSQL_PASSWORD}


### PhpMyAdmin ###
PMA_HOST = ${MYSQL_HOST}
PMA_PORT = ${MYSQL_POST}
MYSQL_HOST_PHPMYADMIN = 192.168.20.30

```

### 2. Vérification de l'Application

1. Accéder à l'application :
```
http://localhost
```

## Dépannage

### Problèmes Courants

#### Problème de Connexion à la Base de Données

1. Vérifier les identifiants dans `.env`
2. Vérifier que MariaDB est en cours d'exécution
3. Vérifier les logs MariaDB


### Support

En cas de problème persistant :
1. Consulter les logs
2. Vérifier la documentation
3. Contacter le support technique 