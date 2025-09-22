# Manuel d'Administration

**Auteur**: REICHHART Damien - BENAD Kilian  
**Date de création**: 23/04/2025 
**Dernière modification**: 23/04/2025

## Sommaire

1. [Introduction](#introduction)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Gestion des Utilisateurs](#gestion-des-utilisateurs)
5. [Maintenance](#maintenance)
6. [Sécurité](#sécurité)

## Introduction

Ce manuel est destiné aux administrateurs de l'application de gestion de conteneurs Docker. Il décrit les procédures d'installation, de configuration et de maintenance du système.

## Gestion des Utilisateurs

### Création d'un Utilisateur
1. Se connecter en tant qu'administrateur
2. Aller dans la section "Utilisateurs"
3. Cliquer sur "Ajouter un utilisateur"
4. Remplir le formulaire
5. Valider

### Modification des Droits
1. Sélectionner l'utilisateur
2. Cliquer sur "Modifier les droits"
3. Ajuster les niveaux d'accès
4. Sauvegarder

### Suppression d'un Utilisateur
1. Sélectionner l'utilisateur
2. Cliquer sur "Supprimer"
3. Confirmer la suppression

## Maintenance

### Sauvegarde
1. **Base de Données**
   ```bash
   docker compose -f dev.yaml run db mysqldump -u root -p[PASSWORD] [DATABASE] > backup.sql
   ```

2. **Fichiers**
   ```bash
   tar -czf backup.tar.gz /chemin/vers/le/projet
   ```

### Mise à Jour
1. Mettre à jour le code source
   ```bash
   git pull
   ```

2. Mettre à jour les dépendances
   ```bash
   make composer-update
   ```

3. Mettre à jour la base de données
   ```bash
   make migrate
   ```

4. Redémarrer les conteneurs
   ```bash
   make down up
   ```

## Sécurité

### Bonnes Pratiques
- Changer régulièrement les mots de passe
- Limiter l'accès SSH
- Mettre à jour régulièrement les composants
- Surveiller les logs

### Surveillance
- Vérifier les logs Apache : `docker logs apache`
- Vérifier les logs PHP : `docker logs php`
- Vérifier les logs MariaDB : `docker logs db`

### Incident de Sécurité
1. Isoler le système
2. Analyser les logs
3. Changer les mots de passe
4. Mettre à jour les certificats
5. Notifier les utilisateurs 