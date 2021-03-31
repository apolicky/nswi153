<?php

/**
 * Special container that wokrs as associative array but it may
 * register regular expressions as keys as well.
 */
class IndexedContainer
{
	private $items = [];		// regular items
	private $regexItems = [];	// items matched by regex keys


	/**
	 * Simple key is a key which is not valid regular expression.
	 * @param string $key
	 */
	private function isSimpleKey(string $key)
	{
		return @preg_match($key, null) === false;	// key is not a valid regular expression
	}

	/**
	 * Construtor which also fills the index using associative array.
	 * @param array $data Initial data to be added.
	 */
	public function __construct(array $data = [])
	{
		foreach ($data as $key => $value) {
			$this->add($key, $value);
		}

		// echo "indexed: __________________\n";
		// print_r($this->items);
		// print_r($this->regexItems);
		// echo "___________________________\n";
	}

	/**
	 * Add new record identified by either simple key or regular expression.
	 * Empty key is considered to be a default (will match any key in find operation).
	 * @param string $keyOrRegex
	 * @param $value
	 */
	public function add(string $keyOrRegex, $value)
	{
		if ($this->isSimpleKey($keyOrRegex)) {
			$this->items[$keyOrRegex] = $value;
		} else {
			$this->regexItems[$keyOrRegex] = $value;
		}
	}

	/**
	 * Find all values that could be associated with given key.
	 * @param string $key
	 * @return array
	 */
	public function find(string $key)
	{
		$res = [];

		// Find the key as simple key (exact match).
		if (array_key_exists($key, $this->items)) {
			$res[] = $this->items[$key];
		}

		// Find first matching regex.
		foreach ($this->regexItems as $regex => $value) {
			if (preg_match($regex, $key)) {
				$res[] = $value;
			}
		}

		// Return default if exists.
		if (array_key_exists('', $this->items)) {
			$res[] = $this->items[''];
		}

		return $res;
	}

	/**
	 * Find exact match of a key or regex value.
	 * @param string $keyOrRegex
	 */
	public function findExactMatch(string $keyOrRegex)
	{
		// Find the key as simple key (exact match).
		if (array_key_exists($keyOrRegex, $this->items)) {
			return $this->items[$keyOrRegex];
		}

		// Find the key as simple key (exact match).
		if (array_key_exists($keyOrRegex, $this->regexItems)) {
			return $this->regexItems[$keyOrRegex];
		}

		return null;
	}
}


/**
 * Helper class that aggregates policy functions (checks) of Project entity.
 * These methods must have exactly two args (user, project) and they can be
 * referred by name from the security model itself.
 */
class SecurityModelProjectACLPolicies
{
	/**
	 * Verify whether the user is a manager of this project or inherits manager priviledges from any of the ancestors.
	 */
	public function isManager(IUser $user, IProject $project): bool
	{
		return array_search($user, $project->getManagers(), true) !== false
			|| ($project->getParentProject() && $this->isManager($user, $project->getParentProject()));
	}


	/*
	 * Place your other policies here ...
	 */

	/**
	 * Verify whether the user is a member of this project or any of the ancestors.
	 */
	public function isTeamMember(IUser $user, IProject $project) : bool 
	{
		return $this->isManager($user, $project) ||
			(array_search($user, $project->getTeamMembers(), true) !== false 
				|| ($project->getParentProject() && $this->isTeamMember($user, $project->getParentProject())));
	}

	public function anyone(IUser $user, object $obj) : bool {
		return true;
	}

	public function isCeo(IUser $user, object $ojb) : bool {
		return $user->getRole() === "ceo";
	}

	public function isDirectMember(IUser $user, IProject $project) : bool {
		return array_search($user, $project->getTeamMembers(), true) !== false 
			|| array_search($user, $project->getManagers(), true) !== false;
	}

	public function isAuditor(IUser $user, object $obj) : bool {
		return $user->getRole() === "auditor";
	}

	public function isAdmin(IUser $user, object $obj) : bool {
		return $user->getRole() === "admin";
	}

	/**
	 * 
	 */
	public function isMemberOfChildProject(IUser $user, IProject $project) : bool {
		foreach($project->getSubProjects() as $sub_project) {
			if(in_array($user, $sub_project->getTeamMembers()) 
			|| in_array($user, $sub_project->getManagers())) {
				return true;
			}
		}
		return false;
	}

}


/**
 * Implementation of the security model using ACL rules.
 */
class SecurityModel implements ISecurityModel
{
	private $aclPolicies = [];
	// private 


	/**
	 * The constructor should fill/load the rules for the model.
	 */
	public function __construct()
	{
		// Dependency injection should have been used here, but let's not complicate things right now...
		$this->aclPolicies['project'] = new SecurityModelProjectACLPolicies();
		// $this->aclPolicies['usr'] = new IndexedContainer();
		// $pr->add()

		/*
		 * Write your ACL initialization code here.
		 * Try to prefer declarative way, so that the ACL rules may be later shifted to configuration.
		 */

		$u = [
			"getFullName" => ["anyone"],
			"getRole" => ["isCeo"],
			"~^get.*~" => ["isAuditor"],
			"~.*~" => ["isAdmin"]
		];

		$this->aclPolicies['usr'] = new IndexedContainer($u);

		$p = [
			"~get.*~" => ["isTeamMember","isAuditor"],
			"~get(Name|Description)~" => ["isMemberOfChildProject"],
			"~^(?!(add|remove)Manager).*~" => ["isManager"],
			"setTaskComplete" => ["isDirectMember"],
			"~^(?!(set|add|delete)Task).*~" => ["isCeo"],
			"~.*~" => ["isAdmin"]
		];

		$this->aclPolicies['proj'] = new IndexedContainer($p);
	}


	public function hasPermissions(IUser $user, object $resource, string $action): bool
	{
		$usr_role = $user->getRole();
		$resource_type = null;

		if($usr_role === 'admin') return true;
		if($usr_role === 'auditor' && preg_match("~get.*~", $action)) return true;


		if($resource instanceof IUser) {
			$resource_type = 'usr';
		}
		else if ($resource instanceof IProject) {
			$resource_type = 'proj';
		}
		else {
			return false;
		}

		$found = $this->aclPolicies[$resource_type]->find($action);
		if($found) {
			$allowed_users = array_merge(...$found);
			
			if($allowed_users !== false) {
				// if at least one is satisfied -> true
				foreach($allowed_users as $usr_specification) {
					if($this->aclPolicies['project']->$usr_specification($user, $resource)) {
						return true;
					}
				}
			}		
		}

		return false;
	}
}
