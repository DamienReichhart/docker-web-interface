<?php /** @noinspection PhpUnused */

namespace App\Model;

class ManageServer extends Model
{
    protected static string $table = 'ManageServers';
    public static array $columns = ['idUsers', 'idServers', 'sshUserManageServers', 'sshUserPasswordManageServers'];
    protected static array $primaryKey = ['idUsers', 'idServers'];

    public static array $foreignKeys = [
        'idUsers' => User::class,
        'idServers' => Server::class
    ];

    final public function setIdUsers(int $getId): void
    {
        $this->attributes['idUsers'] = $getId;
    }

    final public function setIdServers(int|string $getId): void
    {
        $this->attributes['idServers'] = $getId;
    }

    final public function setSshUserManageServers(mixed $sshUserManageServers): void
    {
        $this->attributes['sshUserManageServers'] = $sshUserManageServers;
    }
    final public function getSshUserPasswordManageServers(): string
    {
        return $this->attributes['sshUserPasswordManageServers'];
    }

    final public function setSshUserPasswordManageServers(mixed $sshUserPasswordManageServers): void
    {
        $this->attributes['sshUserPasswordManageServers'] = $sshUserPasswordManageServers;
    }

    final public function getSshUserManageServers(): string
    {
        return $this->attributes['sshUserManageServers'];
    }
}