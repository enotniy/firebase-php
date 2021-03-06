<?php

namespace Kreait\Firebase\Auth;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Kreait\Firebase\Exception\Auth\CredentialsMismatch;
use Kreait\Firebase\Exception\Auth\EmailNotFound;
use Kreait\Firebase\Exception\Auth\InvalidCustomToken;
use Kreait\Firebase\Exception\Auth\InvalidPassword;
use Kreait\Firebase\Exception\Auth\UserDisabled;
use Kreait\Firebase\Exception\AuthException;
use Lcobucci\JWT\Token;
use Psr\Http\Message\ResponseInterface;

class ApiClient
{
    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Takes a custom token and exchanges it with an ID token.
     *
     * @param Token $token
     *
     * @see https://firebase.google.com/docs/reference/rest/auth/#section-verify-custom-token
     *
     * @throws InvalidCustomToken
     * @throws CredentialsMismatch
     *
     * @return ResponseInterface
     */
    public function exchangeCustomTokenForIdAndRefreshToken(Token $token): ResponseInterface
    {
        return $this->request('verifyCustomToken', [
            'token' => (string) $token,
            'returnSecureToken' => true,
        ]);
    }

    /**
     * Creates a new user with the given email address and password.
     *
     * @param string $email
     * @param string $password
     *
     * @see https://firebase.google.com/docs/reference/rest/auth/#section-create-email-password
     *
     * @return ResponseInterface
     */
    public function signupNewUser(string $email = null, string $password = null): ResponseInterface
    {
        return $this->request('signupNewUser', array_filter([
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true,
        ]));
    }

    /**
     * Returns a user for the given email address and password.
     *
     * @param string $email
     * @param string $password
     *
     * @see https://firebase.google.com/docs/reference/rest/auth/#section-sign-in-email-password
     *
     * @throws EmailNotFound
     * @throws InvalidPassword
     * @throws UserDisabled
     *
     * @return ResponseInterface
     */
    public function getUserByEmailAndPassword(string $email, string $password): ResponseInterface
    {
        return $this->request('verifyPassword', array_filter([
            'email' => $email,
            'password' => $password,
            'returnSecureToken' => true,
        ]));
    }

    public function deleteUser(User $user): ResponseInterface
    {
        return $this->request('deleteAccount', [
            'idToken' => (string) $user->getIdToken(),
        ]);
    }

    public function changeUserPassword(User $user, string $newPassword): ResponseInterface
    {
        return $this->request('setAccountInfo', [
            'idToken' => (string) $user->getIdToken(),
            'password' => $newPassword,
            'returnSecureToken' => true,
        ]);
    }

    public function changeUserEmail(User $user, string $newEmail): ResponseInterface
    {
        return $this->request('setAccountInfo', [
            'idToken' => (string) $user->getIdToken(),
            'email' => $newEmail,
            'returnSecureToken' => true,
        ]);
    }

    public function changeUserDisplayName(User $user, string $newName): ResponseInterface
    {
        return $this->request('setAccountInfo', [
            'idToken' => (string) $user->getIdToken(),
            'displayName' => $newName,
        ]);
    }
    
    public function changeUserPhotoUrl(User $user, string $newPhotoUrl): ResponseInterface
    {
        return $this->request('setAccountInfo', [
            'idToken' => (string) $user->getIdToken(),
            'photoUrl' => $newPhotoUrl,
        ]);
    }

    public function changeUser(User $user, array $data): ResponseInterface
    {
        $data['idToken'] = (string) $user->getIdToken();

        return $this->request('setAccountInfo', $data);
    }

    public function sendEmailVerification(User $user): ResponseInterface
    {
        return $this->request('getOobConfirmationCode', [
            'requestType' => 'VERIFY_EMAIL',
            'idToken' => (string) $user->getIdToken(),
        ]);
    }

    public function sendPasswordResetEmail(string $email): ResponseInterface
    {
        return $this->request('getOobConfirmationCode', [
            'email' => $email,
            'requestType' => 'PASSWORD_RESET',
        ]);
    }

    private function request(string $uri, array $data): ResponseInterface
    {
        try {
            return $this->client->request(RequestMethod::METHOD_POST, $uri, ['json' => $data]);
        } catch (RequestException $e) {
            throw AuthException::fromRequestException($e);
        }
    }
}
