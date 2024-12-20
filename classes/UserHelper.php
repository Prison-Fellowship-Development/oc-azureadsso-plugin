<?php

namespace Luketowers\Azureadsso\Classes;

use Backend\Models\User;
use Backend\Models\UserRole;
use Config;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class UserHelper {
    public static function getAuthUser(SocialiteUser $azureUser): User
    {

        $user = User::where('email', $azureUser->getEmail())->firstOr(function () use ($azureUser) {
            $userMap = [
                'givenName'  => 'first_name',
                'surname'    => 'last_name',
            ];

            $newUser = new User();
            $newUser->email = $azureUser->getEmail();
            $newUser->login = $azureUser->getEmail();

            foreach ($userMap as $azureField => $laravelField) {
                $newUser->{$laravelField} = $azureUser->user[$azureField];
            }

            self::userAdjustments($newUser);

            $newUser->save();

            return $newUser;
        });

        $user->azure_id = $azureUser->getId();
        $user->save();

        return $user;
    }

    protected static function userAdjustments(User $user): void
    {
        $pass = str_random(60);
        $user->password = $pass;
        $user->password_confirmation = $pass;

        // Assign the default role if provided
        if ($code = Config::get('services.azure.cms_role_code')) {
            $user->role_id = UserRole::select('id', 'code')->where('code', $code)->first()->id;
        }
    }
}
