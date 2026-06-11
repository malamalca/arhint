<?php
declare(strict_types=1);

namespace App\Lib\DAV;

use Cake\ORM\TableRegistry;
use Sabre\DAV;
use Sabre\DAV\MkCol;
use Sabre\DAVACL\PrincipalBackend\AbstractBackend;
use Sabre\DAVACL\PrincipalBackend\CreatePrincipalSupport;
use Sabre\Uri;

/**
 * PDO principal backend.
 *
 * This backend assumes all principals are in a single collection. The default collection
 * is 'principals/', but this can be overridden.
 */
class ArhintPrincipalBackend extends AbstractBackend implements CreatePrincipalSupport
{
    /**
     * Returns a list of principals based on a prefix.
     *
     * This prefix will often contain something like 'principals'. You are only
     * expected to return principals that are in this base path.
     *
     * You are expected to return at least a 'uri' for every user, you can
     * return any additional properties if you wish so. Common properties are:
     *   {DAV:}displayname
     *   {http://sabredav.org/ns}email-address - This is a custom SabreDAV
     *     field that's actualy injected in a number of other properties. If
     *     you have an email address, use this property.
     *
     * @param string $prefixPath
     * @return array
     */
    public function getPrincipalsByPrefix($prefixPath): array
    {
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $users = $UsersTable->find()
            ->select()
            ->where(['active' => true])
            ->all();

        $principals = [];
        foreach ($users as $user) {
            $principal = [
                'id' => $user->id,
                'uri' => 'principals/' . $user->username,
                '{DAV:}displayName' => $user->name,
                '{http://sabredav.org/ns}email-address' => $user->email,
            ];
            $principals[] = $principal;
        }

        return $principals;
    }

    /**
     * Returns a specific principal, specified by it's path.
     * The returned structure should be the exact same as from
     * getPrincipalsByPrefix.
     *
     * @param string $path
     * @return array
     */
    public function getPrincipalByPath($path): array
    {
        $username = explode('/', $path);

        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $user = $UsersTable->find()
            ->select()
            ->where(['username' => array_pop($username), 'active' => true])
            ->first();

        if (!$user) {
            return [];
        }

        $principal = [
            'id' => $user->id,
            'uri' => 'principals/' . $user->username,
            '{DAV:}displayName' => $user->name,
            '{http://sabredav.org/ns}email-address' => $user->email,
        ];

        return $principal;
    }

    /**
     * Updates one ore more webdav properties on a principal.
     *
     * The list of mutations is stored in a Sabre\DAV\PropPatch object.
     * To do the actual updates, you must tell this object which properties
     * you're going to process with the handle() method.
     *
     * Calling the handle method is like telling the PropPatch object "I
     * promise I can handle updating this property".
     *
     * Read the PropPatch documentation for more info and examples.
     *
     * @param string $path
     */
    public function updatePrincipal($path, DAV\PropPatch $propPatch): void
    {
    }

    /**
     * This method is used to search for principals matching a set of
     * properties.
     *
     * This search is specifically used by RFC3744's principal-property-search
     * REPORT.
     *
     * The actual search should be a unicode-non-case-sensitive search. The
     * keys in searchProperties are the WebDAV property names, while the values
     * are the property values to search on.
     *
     * By default, if multiple properties are submitted to this method, the
     * various properties should be combined with 'AND'. If $test is set to
     * 'anyof', it should be combined using 'OR'.
     *
     * This method should simply return an array with full principal uri's.
     *
     * If somebody attempted to search on a property the backend does not
     * support, you should simply return 0 results.
     *
     * You can also just return 0 results if you choose to not support
     * searching at all, but keep in mind that this may stop certain features
     * from working.
     *
     * @param string $prefixPath
     * @param string $test
     * @return array
     */
    public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof'): array
    {
        if (count($searchProperties) == 0) {
            return [];
        }

        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $q = $UsersTable->find()
            ->select()
            ->where(['active' => true]);

        foreach ($searchProperties as $property => $value) {
            // todo: should be "OR" condition
            switch ($property) {
                case '{DAV:}displayname':
                    $q->andWhere(['name IN' => (array)$value]);
                    break;
                case '{http://sabredav.org/ns}email-address':
                    $q->andWhere(['email IN' => (array)$value]);
                    break;
                default:
                    // Unsupported property
                    return [];
            }
        }
        $users = $q->all();

        $principals = [];
        foreach ($users as $user) {
            $principal = [
                'id' => $user->id,
                'uri' => 'principals/' . $user->username,
                '{DAV:}displayName' => $user->name,
                '{http://sabredav.org/ns}email-address' => $user->email,
            ];
            $principals[] = $principal;
        }

        return $principals;
    }

    /**
     * Finds a principal by its URI.
     *
     * This method may receive any type of uri, but mailto: addresses will be
     * the most common.
     *
     * Implementation of this API is optional. It is currently used by the
     * CalDAV system to find principals based on their email addresses. If this
     * API is not implemented, some features may not work correctly.
     *
     * This method must return a relative principal path, or null, if the
     * principal was not found or you refuse to find it.
     *
     * @param string $uri
     * @param string $principalPrefix
     * @return string|null
     */
    public function findByUri($uri, $principalPrefix): ?string
    {
        $uriParts = Uri\parse($uri);

        // Only two types of uri are supported :
        //   - the "mailto:" scheme with some non-empty address
        //   - a principals uri, in the form "principals/NAME"
        // In both cases, `path` must not be empty.
        if (empty($uriParts['path'])) {
            return null;
        }

        $uri = null;

        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        if ($uriParts['scheme'] === 'mailto') {
            $user = $UsersTable->find()
                ->select()
                ->where(['email' => $uriParts['path'], 'active' => true])
                ->first();

            if ($user) {
                $uri = 'principals/' . $user->username;
            }
        } else {
            $pathParts = Uri\split($uriParts['path']); // We can do this since $uriParts['path'] is not null

            if (count($pathParts) === 2 && $pathParts[0] === $principalPrefix) {
                $user = $UsersTable->find()
                    ->select()
                    ->where(['email' => $pathParts[1], 'active' => true])
                    ->first();

                if ($user) {
                    $uri = 'principals/' . $user->username;
                }
            }
        }

        return $uri;
    }

    /**
     * Returns the list of members for a group-principal.
     *
     * @param string $principal
     * @return array
     */
    public function getGroupMemberSet($principal): array
    {
        $result = [];

        return $result;
    }

    /**
     * Returns the list of groups a principal is a member of.
     *
     * @param string $principal
     * @return array
     */
    public function getGroupMembership($principal): array
    {
        $result = [];

        return $result;
    }

    /**
     * Updates the list of group members for a group principal.
     *
     * The principals should be passed as a list of uri's.
     *
     * @param string $principal
     */
    public function setGroupMemberSet($principal, array $members): void
    {
    }

    /**
     * Creates a new principal.
     *
     * This method receives a full path for the new principal. The mkCol object
     * contains any additional webdav properties specified during the creation
     * of the principal.
     *
     * @param string $path
     */
    public function createPrincipal($path, MkCol $mkCol): void
    {
    }
}
