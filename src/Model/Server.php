<?php /** @noinspection PhpUnused */

namespace App\Model;

class Server extends Model
{
    protected static string $table = 'Servers';
    public static array $columns = ['id', 'nameServers', 'host', 'urlIdentifierServer', 'description'];


    final public function setNameServers(mixed $nameServer): void
    {
        $this->attributes['nameServers'] = $nameServer;
    }

    final public function setHost(mixed $host): void
    {
        $this->attributes['host'] = $host;
    }

    final public function setDescription(mixed $description): void
    {
        $this->attributes['description'] = $description;
    }

    final public function getId(): int
    {
        return $this->attributes['id'];
    }

    final public function getUrlIdentifierServer(): String
    {
        return $this->attributes['urlIdentifierServer'];
    }

    final public function getNameServers(): String
    {
        return $this->attributes['nameServers'];
    }

    final public function getHost(): String
    {
        return $this->attributes['host'];
    }

    final public function getDescription(): ?String
    {
        return $this->attributes['description'] ?? null;
    }
}
