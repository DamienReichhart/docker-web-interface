<div style="text-align: left;">     <img src="./assets/img/logo.png" alt="Logo" style="width: 100px;"/> </div>

>># Interface Web pour la Gestion de Docker
>>
>>Auteur : REICHHART Damien | BENAD Killian
>>
>>Date de création : 28/10/2024
>>
>>Dernière modification : 28/10/2024
>>
>>Sommaire :
>
>[toc]





## Description

Ce projet vise à créer une interface web intuitive pour gérer Docker sur différents serveurs. Les fonctionnalités permettent à chaque utilisateur de gérer ses propres instances de conteneurs Docker de manière sécurisée, avec un accès exclusif à ses serveurs. Il inclut également un espace pour ajouter des Dockerfiles personnalisés, accessibles à tous les utilisateurs pour simplifier le déploiement.

## Fonctionnalités

- **Gestion des serveurs** : possibilité d'ajouter et de gérer plusieurs serveurs Docker.
- **Gestion des conteneurs Docker par utilisateur** : chaque utilisateur peut gérer ses propres instances sur ses serveurs sans interférer avec celles des autres.
- **Dockerfiles partagés** : ajout de Dockerfiles personnalisés accessibles à tous les utilisateurs pour faciliter le déploiement des conteneurs.
- **Sécurité des accès** : Chaque utilisateur ne voit et ne gère que ses propres instances Docker.

## Installation

1. Clonez le dépôt :

	```bash
	git clone https://gitlab.com/DamienReichhart/CCI-BTS-SIO-23-25-Atelier-Professionnalisation-3.git
	```

2. Configurez les variables d'environnement dans le fichier `.env`.

3. Lancez le conteneur Docker et initialiser la base de donnée pour démarrer l'application :

	```
	make up composer-install migrate
	```

## Utilisation

1. Connectez-vous à l'interface web.
2. Ajoutez un serveur Docker.
3. Gérez vos conteneurs Docker directement depuis l'interface.
4. Accédez aux Dockerfiles partagés pour déployer de nouvelles applications.

## Contributeurs

- **Damien Reichhart**
- **Kilian Benad**
