# Architecture Technique

**Auteur**: REICHHART Damien - BENAD Kilian  
**Date de création**: 23/04/2025
**Dernière modification**: 23/04/2025

## Sommaire

1. [Introduction](#introduction)
2. [Architecture Générale](#architecture-générale)
3. [Composants](#composants)
4. [Flux de Données](#flux-de-données)
5. [Sécurité](#sécurité)

## Introduction

Ce document décrit l'architecture technique de l'application de gestion de conteneurs Docker. Il présente la structure du système, les composants principaux et leurs interactions.

## Architecture Générale

L'application suit une architecture MVC (Modèle-Vue-Contrôleur) avec les caractéristiques suivantes :

- **Frontend**: Interface web en PHP avec Twig
- **Backend**: API REST en PHP
- **Base de données**: MariaDB
- **Containerisation**: Docker
- **Sécurité**: Middleware d'authentification et d'autorisation

## Composants

### 1. Structure du Projet

```
project-root/
├── src/
│   ├── Controller/     # Contrôleurs MVC
│   ├── Model/          # Modèles de données
│   ├── Service/        # Services métier
│   ├── Middleware/     # Middleware de sécurité
│   ├── Helper/         # Fonctions utilitaires
│   └── Enum/           # Énumérations
├── template/           # Templates Twig
├── public/            # Point d'entrée public
├── docker/            # Configuration Docker
└── sql/              # Scripts SQL
```

### 2. Composants Principaux

#### Contrôleurs
- `AuthController`: Gestion de l'authentification
- `ServerController`: Gestion des serveurs
- `ContainerController`: Gestion des conteneurs
- `UserController`: Gestion des utilisateurs
- `DockerfileController`: Gestion des Dockerfiles

#### Services
- Services de gestion Docker
- Services d'authentification
- Services de gestion des utilisateurs

#### Middleware
- `SecurityMiddleware`: Vérification des droits d'accès
- `LogMiddleware`: Journalisation des actions

## Flux de Données

1. **Authentification**
   - Vérification des identifiants
   - Stockage en session

2. **Gestion des Conteneurs**
   - Requête API Docker
   - Traitement des données
   - Mise à jour de la base de données
   - Retour des résultats

3. **Gestion des Utilisateurs**
   - Validation des données
   - Chiffrement des mots de passe
   - Mise à jour de la base de données

## Sécurité

### 1. Authentification
- Sessions sécurisées

### 2. Autorisation
- Niveaux de sécurité (1-3)
- Vérification des droits par middleware
- Logging des actions sensibles

### 3. Protection des Données
- Chiffrement des mots de passe
- Validation des entrées
- Protection contre les injections SQL

### 4. Sécurité Réseau
- HTTPS obligatoire
- Configuration sécurisée d'Apache
- Isolation des conteneurs Docker 