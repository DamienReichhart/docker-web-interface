<?php


namespace App;





class Filters{
    private static array $collumn_filter = [
        'IdServers' => 'Serveur',
        'SshUserManageServers' => 'Utilisateur',
        'SshUserPasswordManageServers' => 'Mot de passe',
        'Server' => 'serveur',
        'ManageServer' => "accès au serveur",
        'NameServers' => 'Nom du serveur',
        'Host' => 'Hôte',
    ];
    public static function collumnToName(string $str): string
    {
        return self::$collumn_filter[$str] ?? $str;
    }

    public static function removeFirstCharacter(string $str): string
    {
        return substr($str, 1);
    }
}