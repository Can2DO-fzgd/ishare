<?php
namespace Topxia\Service\User\AuthProvider;

interface AuthProvider
{

    public function register($registration);

    public function syncLogin($userId);

    public function syncLogout($userId);

    public function changeUserName($userId, $newName);

    public function changeEmail($userId, $password, $newEmail);

    public function changePassword($userId, $oldPassword, $newPassword);

    public function checkUsername($username);

    public function checkEmail($email);

    public function checkPassword($userId, $password);

    public function checkLoginById($userId, $password);

    public function checkLoginByUserName($userName, $password);

    public function checkLoginByEmail($email, $password);

    public function getAvatar($userId, $size = 'middle');

    public function getProviderName();

}