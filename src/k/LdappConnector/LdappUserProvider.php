<?php namespace k\LdappConnector;

use adLDAP\adLDAP;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider as UserProviderInterface;

class LdappUserProvider implements UserProviderInterface
{
    /**
     * Stores connection to LDAP.
     * @var adLDAP
     */
    protected $adldap;

    /**
     * The model instance to return when authenticated
     * @var string
     */
    protected $model;

    /**
     * The identifier used to authenticate the user on
     * the local database after the ldap attemp (if success).
     * @var string
     */
    protected $identifier;

    /**
     * Creates a new LdapUserProvider and connect to Ldap
     * @param array $config
     * @param string $model
     */
    public function __construct($config, $model)
    {
        $this->adldap = new adLDAP($config);
        $this->model = $model;
    }

    /**
     * Retrieve a user by their unique identifier.
     * @param  mixed $identifier
     * @return Authenticatable
     */
    public function retrieveById($identifier)
    {
        $userInfo = $this->adldap->user()->info($identifier, array('*'))[0];

        $credentials = array();
        $credentials['username'] = $identifier;

        foreach ($userInfo as $key => $value) {
            $credentials[$key] = $value[0];
        }

        return $this->attemptLocally($credentials);
    }

    /**
     * Retrieve a user by by their unique identifier and "remember me" token.
     * @param  mixed $identifier
     * @param  string $token
     * @return Authenticatable|null
     */
    public function retrieveByToken($identifier, $token)
    {
        // TODO: Implement retrieveByToken() method.
    }

    /**
     * @param Authenticatable $user
     * @param string $token
     */
    public function updateRememberToken(Authenticatable $user, $token)
    {
        // TODO: Implement updateRememberToken() method.
    }

    /**
     * Retrieve a user by the given credentials.
     * @param  array $credentials
     * @return Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if ($this->adldap->authenticate($credentials['username'], $credentials['password'])) {
            $userInfo = $this->adldap->user()->info($credentials['username'], array('*'))[0];

            foreach ($userInfo as $key => $value) {
                $credentials[$key] = $value[0];
            }

            return $this->attemptLocally($credentials);
        }
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        $username = $credentials['username'];
        $password = $credentials['password'];

        return $this->adldap->authenticate($username, $password);
    }

    /**
     * Create a new instance of the model.
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createModel()
    {
        $class = '\\' . ltrim($this->model, '\\');

        return new $class;
    }

    /**
     * Set the identifier used to check the user in local database.
     * @param string $identifier
     * @return LdappUserProvider
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Authenticate the user again to check if it does exists in the database.
     * This call is executed only if the user does exists in the active directory dictionary.
     * @param  array $credentials
     * @return Authenticatable|null
     */
    public function attemptLocally($credentials)
    {
        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // Eloquent User "model" that will be utilized by the Guard instances.
        $query = $this->createModel()->newQuery();

        $query->where($this->identifier, $credentials[$this->identifier]);

        return $query->first();
    }
}
