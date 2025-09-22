<?php /** @noinspection PhpUndefinedFieldInspection */

/** @noinspection PhpUnused */

namespace App\Model;

use App\Enum\UserRoleEnum;
use Exception;

class User extends Model
{
    protected static string $table = 'Users';
    public static array $columns = ['id', 'usernameUsers', 'emailUsers', 'passwordUsers','idRoles'];

    // Define foreign keys if any
    public static array $foreignKeys = [
        'idRoles' => Role::class
    ];

    public static function insertUser(string $username, string $email, string $password, string|int $idRoles): void
    {
        $user = new User();
        $user->usernameUsers = $username;
        $user->emailUsers = $email;
        $user->idRoles = $idRoles;
        $user->passwordUsers = password_hash($password, PASSWORD_DEFAULT);
        $user->save();
    }

    final public function getPassword(): string
    {
        return $this->attributes['passwordUsers'];
    }

    final public function getId(): int
    {
        return $this->attributes['id'];
    }

    final public function setPassword(string $password)
    {
        $this->attributes['passwordUsers'] = password_hash($password, PASSWORD_DEFAULT);
    }
    
    final public function setEmail(string $email)
    {
        $this->attributes['emailUsers'] = $email;
    }

    /**
     * @throws Exception
     */
    final public function isAdmin(): bool
    {
        return  Role::getWhere(['id' => $this->attributes['idRoles']])[0]->nameRoles === UserRoleEnum::toString(UserRoleEnum::ADMIN);
    }
}
