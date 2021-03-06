<?php
namespace Search\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Event\Event;
use Cake\Utility\Hash;

class PrgComponent extends Component
{

    /**
     * Default config
     *
     * ### Options
     * - `actions` : Action name(s) to use PRG for. You can pass a single action
     *   as string or multiple as array. If boolean `true` all actions will be
     *   processed if `false` none. Default is ['index', 'lookup'].
     * - `queryStringWhitelist` : An array of whitelisted query strings to be kept.
     *   Defaults to the Paginator `'sort'`, `'direction'` and `'limit'` ones.
     * - `queryStringBlacklist` : An array of form fields that should not end up in the query.
     * - `emptyValues` : A map of fields and their values to be considered empty
     *   (will not be passed along in the URL).
     *
     * @var array
     */
    protected $_defaultConfig = [
        'actions' => ['index', 'lookup'],
        'queryStringWhitelist' => ['sort', 'direction', 'limit'],
        'queryStringBlacklist' => ['_csrfToken', '_Token'],
        'emptyValues' => [],
    ];

    /**
     * Checks if the current request has posted data and redirects the users
     * to the same action after converting the post data into GET params
     *
     * @return \Cake\Http\Response|null
     */
    public function startup()
    {
        if (!$this->request->is('post') || !$this->_actionCheck()) {
            return null;
        }

        list($url) = explode('?', $this->request->here(false));

        $params = $this->_filterParams();
        if ($params) {
            $url .= '?' . http_build_query($params);
        }

        return $this->_registry->getController()->redirect($url);
    }

    /**
     * Checks if the action should be processed by the component.
     *
     * @return bool
     */
    protected function _actionCheck()
    {
        $actions = $this->getConfig('actions');
        if (is_bool($actions)) {
            return $actions;
        }

        return in_array($this->request->getParam('action'), (array)$actions, true);
    }

    /**
     * Filters the params from POST data and merges in the whitelisted query string ones.
     *
     * @return array
     */
    protected function _filterParams()
    {
        $params = Hash::filter($this->request->getData());

        foreach ((array)$this->getConfig('queryStringBlacklist') as $field) {
            unset($params[$field]);
        }

        foreach ((array)$this->getConfig('emptyValues') as $field => $value) {
            if (!isset($params[$field])) {
                continue;
            }

            if ($params[$field] === (string)$value) {
                unset($params[$field]);
            }
        }

        foreach ((array)$this->getConfig('queryStringWhitelist') as $field) {
            $value = $this->request->getQuery($field);
            if ($value !== null && !isset($params[$field])) {
                $params[$field] = $value;
            }
        }

        return $params;
    }
}
