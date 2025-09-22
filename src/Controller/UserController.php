<?php

namespace App\Controller;

use App\Entity\Form\BasicForm;
use App\Enum\UserRoleEnum;
use App\Model\Role;
use App\Model\User;
use Exception;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class UserController extends FrontController
{
    /**
     * Display a list of all users
     * 
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final public function index(): string
    {
        $users = User::all();
        return $this->render('admin/users.twig', [
            'users' => $users,
            'page_title' => 'Gestion des utilisateurs'
        ]);
    }

    /**
     * Show the form to add a new user
     * 
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     * @throws Exception
     */
    final public function add($errors = []): string
    {
        // Get all roles for the dropdown
        $roles = Role::all();
        $roleOptions = [];
        foreach ($roles as $role) {
            $roleOptions[$role->id] = $role->nameRoles;
        }
        
        $form = new BasicForm("Ajouter un utilisateur", '/user/add/post');
        $form->addLine('Nom d\'utilisateur', 'usernameUsers', 'text', '');
        $form->addLine('Email', 'emailUsers', 'email', '');
        $form->addLine('Mot de passe', 'passwordUsers', 'password', '');
        $form->addLine('Confirmer le mot de passe', 'passwordConfirm', 'password', '');
        $form->addLine('Rôle', 'idRoles', 'select', $roleOptions);
        
        return $this->render('form.twig', [
            'form' => $form,
            'page_title' => 'Ajouter un utilisateur',
            'errors' => $errors
        ]);
    }

    /**
     * Process the add user form submission
     * 
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final public function addPost(): string
    {
        $email = $this->httpRequest->getPostElement('emailUsers');
        $password = $this->httpRequest->getPostElement('passwordUsers');
        $passwordConfirm = $this->httpRequest->getPostElement('passwordConfirm');
        $username = $this->httpRequest->getPostElement('usernameUsers');
        $idRole = $this->httpRequest->getPostElement('idRoles');
        
        // Validation
        $errors = [];
        
        if (empty($username)) {
            $errors[] = "Le nom d'utilisateur est requis";
        }
        
        if (empty($email)) {
            $errors[] = "L'email est requis";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format d'email invalide";
        }
        
        if (empty($password)) {
            $errors[] = "Le mot de passe est requis";
        } elseif (strlen($password) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        } elseif ($password !== $passwordConfirm) {
            $errors[] = "Les mots de passe ne correspondent pas";
        }
        
        // Check if email already exists
        try {
            $existingUsers = User::getWhere(['emailUsers' => $email]);
            if (!empty($existingUsers)) {
                $errors[] = "Cet email est déjà utilisé";
            }
        } catch (Exception $e) {
            // No users found with this email, which is what we want
        }
        
        if (!empty($errors)) {
            return $this->add($errors);
        }
        
        // Create and save the user
        try {
            User::insertUser($username, $email, $password, $idRole);
            $this->redirect('/user');
        } catch (Exception $e) {
            return $this->render('form.twig', [
                'errors' => ["Une erreur est survenue lors de l'ajout de l'utilisateur: " . $e->getMessage()],
                'page_title' => 'Erreur'
            ]);
        }
        
        return '';
    }

    /**
     * Show the form to edit a user
     * 
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     * @throws Exception
     */
    final public function edit(string $userId = null): string 
    {
        try {
            // If no userId provided, use current user's ID
            if (!$userId) {
                $userId = $this->session->get('user_id');
            }
            
            $user = new User(['id' => $userId]);
            
            // Get all roles for dropdown if admin
            $roles = [];
            if ($this->userLogged && $this->userLogged->isAdmin()) {
                $allRoles = Role::all();
                foreach ($allRoles as $role) {
                    $roles[$role->id] = $role->nameRoles;
                }
            }
            
            $form = new BasicForm("Modifier l'utilisateur", '/user/edit/' . $userId . '/post');
            $form->addLine('Nom d\'utilisateur', 'usernameUsers', 'text', $user->usernameUsers);
            $form->addLine('Email', 'emailUsers', 'email', $user->emailUsers);
            $form->addLine('Nouveau mot de passe', 'passwordUsers', 'password', '');
            $form->addLine('Confirmer le mot de passe', 'passwordConfirm', 'password', '');
            
            // Only admins can change roles
            if (!empty($roles)) {
                $form->addLine('Rôle', 'idRoles', 'select', $roles);
            }
            
            return $this->render('form.twig', [
                'form' => $form,
                'user' => $user,
                'page_title' => 'Modifier l\'utilisateur'
            ]);
            
        } catch (Exception $e) {
            return $this->render('error/404.twig', [
                'message' => 'Utilisateur non trouvé'
            ]);
        }
    }

    /**
     * Process the edit user form submission
     * 
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final public function editPost(string $userId): string 
    {
        try {
            $user = new User(['id' => $userId]);
            
            $username = $this->httpRequest->getPostElement('usernameUsers');
            $email = $this->httpRequest->getPostElement('emailUsers');
            $password = $this->httpRequest->getPostElement('passwordUsers');
            $passwordConfirm = $this->httpRequest->getPostElement('passwordConfirm');
            $idRole = $this->httpRequest->getPostElement('idRoles');
            
            // Validation
            $errors = [];
            
            if (empty($username)) {
                $errors[] = "Le nom d'utilisateur est requis";
            }
            
            if (empty($email)) {
                $errors[] = "L'email est requis";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Format d'email invalide";
            }
            
            // Only validate password if it's not empty (changing password is optional)
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
                } elseif ($password !== $passwordConfirm) {
                    $errors[] = "Les mots de passe ne correspondent pas";
                }
            }
            
            // Check if email already exists for other users
            try {
                $existingUsers = User::getWhere(['emailUsers' => $email]);
                foreach ($existingUsers as $existingUser) {
                    if ($existingUser->id != $userId) {
                        $errors[] = "Cet email est déjà utilisé";
                        break;
                    }
                }
            } catch (Exception $e) {
                // No other users with this email, which is what we want
            }
            
            if (!empty($errors)) {
                // Get all roles for dropdown if admin
                $roles = [];
                if ($this->userLogged && $this->userLogged->isAdmin()) {
                    $allRoles = Role::all();
                    foreach ($allRoles as $role) {
                        $roles[$role->id] = $role->nameRoles;
                    }
                }
                
                $form = new BasicForm("Modifier l'utilisateur", '/user/edit/' . $userId . '/post');
                $form->addLine('Nom d\'utilisateur', 'usernameUsers', 'text', $username);
                $form->addLine('Email', 'emailUsers', 'email', $email);
                $form->addLine('Nouveau mot de passe', 'passwordUsers', 'password', '');
                $form->addLine('Confirmer le mot de passe', 'passwordConfirm', 'password', '');
                
                // Only admins can change roles
                if (!empty($roles)) {
                    $form->addLine('Rôle', 'idRoles', 'select', $roles);
                }
                
                return $this->render('form.twig', [
                    'form' => $form,
                    'errors' => $errors,
                    'page_title' => 'Modifier l\'utilisateur'
                ]);
            }
            
            // Update user information
            $user->usernameUsers = $username;
            $user->emailUsers = $email;
            
            // Only update password if provided
            if (!empty($password)) {
                $user->setPassword($password);
            }
            
            // Only update role if admin and role provided
            if ($this->userLogged && $this->userLogged->isAdmin() && !empty($idRole)) {
                $user->idRoles = $idRole;
            }
            
            $user->save();
            
            $this->redirect('/user?success');
            
        } catch (Exception $e) {
            return $this->render('error/500.twig', [
                'message' => 'Une erreur est survenue lors de la modification de l\'utilisateur: ' . $e->getMessage()
            ]);
        }
        
        return '';
    }

    /**
     * Delete a user
     * 
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    final public function delete(string $userId): string 
    {
        try {
            // Only admins can delete users
            if (!$this->userLogged || !$this->userLogged->isAdmin()) {
                return $this->render('error/403.twig', [
                    'message' => 'Vous n\'avez pas les permissions nécessaires pour effectuer cette action'
                ]);
            }
            
            // Can't delete yourself
            if ($userId == $this->userLogged->getId()) {
                return $this->render('error/400.twig', [
                    'message' => 'Vous ne pouvez pas supprimer votre propre compte'
                ]);
            }
            
            $user = new User(['id' => $userId]);
            $user->delete();
            
            $this->redirect('/user');
            
        } catch (Exception $e) {
            return $this->render('error/500.twig', [
                'message' => 'Une erreur est survenue lors de la suppression de l\'utilisateur: ' . $e->getMessage()
            ]);
        }
        
        return '';
    }
}