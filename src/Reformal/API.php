<?php namespace Reformal;

/**
 * Description of API
 * http://reformal.ru/demo/ReformalAPI.pdf
 * 
 * @author "etconsilium@users.noreply.github.com"
 * 
 * @require http://php.net/curl
 */

class API {
    protected $uri = 'http://reformal.ru/service.php';
    
    protected $api_id = null;
    protected $project_id = null;

    protected $token = null;

    public function __construct($params=array()) {
        if (!empty($params['uri'])) {
            $this->uri = $params['uri'];
        }
        if (!empty($params['api_id'])) {
            $this->api_id = $params['api_id'];
        }
        if (!empty($params['project_id'])) {
            $this->project_id = $params['project_id'];
        }
    }
    
    public function getUri() {
        return $this->uri;
    }

    public function getApiId() {
        return $this->api_id;
    }

    public function getProjectId() {
        return $this->project_id;
    }

    public function getToken() {
        return $this->token;
    }

    public function setUri($uri) {
        $this->uri = $uri;
    }

    public function setApiId($api_id) {
        $this->api_id = $api_id;
    }

    public function setProjectId($project_id) {
        $this->project_id = $project_id;
    }

    /**
     * 
     * @param string $xml
     * @return array
     */
    protected function sendRequest(string $xml) {
        $ch = curl_init();
        $options = array(
            CURLOPT_URL => $this->uri,
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => 0,
        );
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);
        $res_xml = simplexml_load_string($response);
        return json_decode( json_encode($res_xml), true );
    }

    public function signIn($email, $psswd) {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
            . "<signIn>"
                . "<email>{$email}</email>"
                . "<password>{$psswd}</password>"
            . "</signIn>";
        $res = $this->sendRequest($xml);
        $this->token = (string)$res->session->token;
        return $this->token;
    }

    /**
     * get user's info, projects, ideas or comments
     * 
     * @param mixed $login id int, or login string
     * @param string $mode personal|projects|ideas|comments
     * @return type
     */
	public function userInfo($login, $mode = 'personal') {
        if (is_string($login)) {
            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
                . "<userInfo>"
                    . "<token>{$this->token}</token>"
                    . "<login>{$login}</login>"
                    . "<mode>{$mode}</mode>"
                . "</userInfo>";
        }
        elseif (is_int($login)) {
            $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
                . "<userInfo>"
                    . "<token>{$this->token}</token>"
                    . "<id>{$login}</id>"
                    . "<mode>{$mode}</mode>"
                . "</userInfo>";
        }
        $res = $this->sendRequest($xml);
		switch ($mode) :
			case 'personal':
				return $res['user'];
			case 'comments':
				return $res['userComments']['comments'];
			case 'ideas';
				return $res['userIdeas']['ideas'];
			case 'projects';
				return $res['userProjects']['projects'];
			default:
				return $res['user'];
		endswitch;
	}
    
    public function userComments($login) {
        return $this->userInfo($login, 'comments');
    }

    public function userProjects($login) {
        return $this->userInfo($login, 'projects');
    }

    public function userIdeas($login) {
        return $this->userInfo($login, 'ideas');
    }
    
    public function ideaInfo($id, $with_coms = false) {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
            . "<ideaInfo>"
                . "<id>{$id}</id>"
                . "<token>{$this->token}</token>"
                . "<with_coms>{$with_coms}</with_coms>" //  здесь неявное преобразование
                . "<api_id>{$this->api_id}</api_id>"
                . "<project_id>{$this->project_id}</project_id>"
            . "</ideaInfo>";
                    
        $res = $this->sendRequest($xml);
        
        return $with_coms ? $res['idea'] : $res['idea']['info'];
	}

    public function ideaComments($id) {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
            . "<ideaComments>"
                . "<id>{$id}</id>"
                . "<token>{$this->token}</token>"
                . "<api_id>{$this->api_id}</api_id>"
                . "<project_id>{$this->project_id}</project_id>"
            . "</ideaComments>";
        $res = $this->sendRequest($xml);
		return $res['comments'];
	}
    
    public function addIdea($project_id, $domain, $title, $story, $params = array()) {
        //  непродуманность апи в плане ид
        
        $params = $params + array('noreg_name'=>false, 'noreg_email'=>false, 'subscribe'=>false);
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
            . "<addIdea>"
                . "<token>{$this->token}</token>"
                . "<api_id>{$this->api_id}</api_id>"
                . "<project_id>{$project_id}</project_id>"
                . "<domain>{$domain}</domain>"
                . "<title>{$title}</title>"
                . "<story>{$story}</story>"
                . "<noreg_name>{$params[noreg_name]}</noreg_name>"
                . "<noreg_email>{$params[noreg_email]}</noreg_email>"
                . "<subscribe>{$params[subscribe]}</subscribe>"
            . "</addIdea>";
        $res = $this->sendRequest($xml);
		return $res['operationStatus'];
	}

    public function addIdeaComment($idea_id, $story, $params = array()) {
        $params = $params + array('noreg_name'=>false, 'noreg_email'=>false, 'parent'=>false);
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
            . "<addIdeaComment>"
                . "<token>{$this->token}</token>"
                . "<idea_id>{$idea_id}</idea_id>"
                . "<story>{$story}</story>"
                . "<noreg_name>{$params[noreg_name]}</noreg_name>"
                . "<noreg_email>{$params[noreg_email]}</noreg_email>"
                . "<parent>{$params[subscribe]}</parent>"
            . "</addIdeaComment>";
		$res = $this->sendRequest($xml);
		return $res['operationStatus'];
	}

    public function deleteIdea($idea_id) {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
            . "<deleteIdea>"
                . "<token>{$this->token}</token>"
                . "<idea_id>{$idea_id}</idea_id>"
                . "<api_id>{$this->api_id}</api_id>"
                . "<project_id>{$project_id}</project_id>"
            . "</deleteIdea>";
		$res = $this->sendRequest($xml);
		return $res['operationStatus'];
	}
    
    public function deleteIdeaComment($comment_id) {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
            . "<deleteIdeaComment>"
                . "<token>{$this->token}</token>"
                . "<comment_id>{$comment_id}</comment_id>"
                . "<api_id>{$this->api_id}</api_id>"
                . "<project_id>{$project_id}</project_id>"
            . "</deleteIdeaComment>";
		$res = $this->sendRequest($xml);
		return $res['operationStatus'];
	}
    
	public function addVote($idea_id) {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
            . "<vote>"
                . "<token>{$this->token}</token>"
                . "<idea_id>{$idea_id}</idea_id>"
                . "<api_id>{$this->api_id}</api_id>"
                . "<project_id>{$project_id}</project_id>"
            . "</vote>";
		$res = $this->sendRequest($xml);
		return $res['operationStatus'];
	}

    public function cancelVote($idea_id) {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
    		. "<cancelVote>"
                . "<token>{$this->token}</token>"
                . "<idea_id>{$idea_id}</idea_id>"
                . "<api_id>{$this->api_id}</api_id>"
                . "<project_id>{$project_id}</project_id>"
            . "</cancelVote>";
		$res = $this->sendRequest($xml);
		return $res['operationStatus'];
    }
    
	public function searchIdea($idea_id, $query) {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
			. "<suggestionSearch>"
                . "<token>{$this->token}</token>"
                . "<api_id>{$this->api_id}</api_id>"
                . "<project_id>{$project_id}</project_id>"
                . "<query>{$query}</query>"
			. "</suggestionSearch>";
		$res = $this->sendRequest($xml);
		return $res;    //  @TODO: в документации не указан формат ответа
	}

    public function regUser($login, $email, $psswd) {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>"
            . "<regUser>"
                . "<login>{$login}</login>"
                . "<email>{$email}</email>"
                . "<password>{$psswd}</password>"
            . "</regUser>";
		$res = $this->sendRequest($xml);
		if (!empty($res['token'])) {
			return $this->token = $res['token'];
        }
		else {
			throw new \Exception('необработанная ошибка. log: '.  var_export($res, 1));
        }
    /**
     * 
        В качестве ответа выступает token (т.е пользователь автоматически авторизуется) или
        сообщения об ошибке с перечнем кодов ошибок:
        17 - длина пароля больше 20 символов
        16 - длина пароля меньше 3 символов
        9 - пользователь с такой почтой уже существует
        8 - длина пароля больше 64
        7 - некорректный email
        6 - путой email
        5 - логин занят
        4 - недопустимый логин
        3 - логин длиннее 18 символов
        2 - логин короче 2-х символов
        1 - пустой логин
        На данный момент введено системное ограничение, не позволяющее регистрировать
        более 1 аккаунта в минуту
     * 
     */
    }
}
