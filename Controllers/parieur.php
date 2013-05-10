<?php
session_start();
include_once ("Models/parieur.php");
include_once ("Models/match.php");
include_once ("Models/paris.php");
//include_once ("Controllers/security.php");
//include "pronostic2.php";
//header('Content-type:text/plain');
class Parieur {

	public $id_parieur;
	public $nom_parieur;
	public $email;
	public $password;
	private $p_model;
	private $m_model;
	private $paris_model;

	function __construct($page){
		//echo $_SESSION['Id_parieur'];
		$this->p_model = new Parieur_model();
		$this->m_model= new Match_model();
		$this->paris_model= new Paris_model();
		switch ($page){
			case"":
			case "login":
				$this->view();
			case "ranking":
				$this->classementView();
				break;
			case "stat":
				$this->statView();
				break;
			case "simulation":
				$this->simulationView();
				break;
			case "updatesimulation":
				$this->updateSimulation();
				break;
			case "input":
				$this->inputView();
				break;
			case "signin":
				$this->login(ucfirst($_POST['name']),$_POST['password']);
				break;
			case "signup":
				$this->validate($_POST['name'],$_POST['email'],$_POST['password'],$_POST['RePassword']);
				break;
			case "table":
				$this->tableView();
				break;
			case "logout":
				$this->logout();
				break;
			case "delete":
				$this->deleteView();
				break;
			case "remove":
				$this->deleteParieur();
				break;
			case "update":
				$this->UpdateProno();
				break;
			default:
				$this->tableView();
				break;
		}
	}

	function logout(){
		session_destroy();
		header('Location: login');
		exit();
	}

	function deleteParieur(){
		//echo $_POST['player_id'];
		$this->p_model->deleteParieur($_POST['player_id']);
		//echo "after";
		header('Location: delete');
		exit();
	}

	function UpdateProno(){
		//echo "avant";
		//var_dump($_POST);
		$this->paris_model->updateParis($_POST);
		//echo "after";
		header('Location: table');
		exit();
	}


	function view($page="login",$message=""){
		$name=$this->nom_parieur;
		$email=$this->email;		
		include "Views/".$page."_view.php";
		exit();
	}

	function checkLoggedIn(){
		//echo "checkloggedin ".$this->id_parieur;
		//var_dump($this->id_parieur);
		if(!isset($_SESSION['Id_parieur'])){
			$message = "Please SignIn to access this page";
			$this->view("login",$message);
		}
	}

	function checkAdmin(){
		//echo "checkloggedin ".$this->id_parieur;
		//var_dump($this->id_parieur);
		if($_SESSION['Id_parieur']!=1){
			$message = "Please SignIn As Admin to access this page";
			session_destroy();
			$this->view("login",$message);
		}
	}

	function deleteView(){
		$this->checkAdmin();
		$parieurs = $this->p_model->getParieurs();
		//var_dump($parieurs);
		include "Views/header.php";
		include("Views/delete_view.php");
		exit();
	}

	function tableView(){
		$this->checkLoggedIn();
		//echo "tavle prono global";

		$parieurs = $this->p_model->getParieurs();
		//echo "tavle prono parieur=>".$_SESSION['Id_parieur'];

		$NombreParieurs=$this->p_model->getNombreParieurs();
		$groupes = $this->m_model->getGroupes();
		//var_dump($_SESSION['matchs']);
		$matchs = $this->getMatchs();
		//$match=$matchs[1];
		//var_dump($match->equipe1);
		$correspondance = $this->m_model->getCorrespondances();
		$paris = $this->paris_model->getParis();
		$points = $this->paris_model->calculate_points($paris,$matchs,true);
		//$equipe=1;$match=5;
		//var_dump($parieurs);
		//var_dump($groupes);
		//var_dump($paris[0]->{'nb_but_e'.$equipe."_m".$match});
		
		include "Views/header.php";
		include "Views/table_view.php";
		exit();
	}

	function classementView(){
		$this->checkLoggedIn();
		//echo "tavle prono global";
		//echo "classement=>".$_SESSION['Id_parieur'];

		$parieurs = $this->p_model->getParieurs();
		//$parieursTranspose = $this->p_model->parieurTranspose($parieurs);
		$NombreParieurs=$this->p_model->getNombreParieurs();
		$groupes = $this->m_model->getGroupes();
		$matchs = $this->getMatchs();
		$correspondance = $this->m_model->getCorrespondances();
		$paris = $this->paris_model->getParis();
		$points = $this->paris_model->calculate_points($paris,$matchs,true);
		$sortedtotalpoints = $this->paris_model->calculateTotalPoints($points);
		//echo"la";
		list($csens,$psens,$cscore,$pscore)=$this->paris_model->countBonSensScore($paris,$matchs);
		$nextGame=$this->m_model->getNextMatch();
		//var_dump($nextGame);
		include "Views/header.php";
		include "Views/classement_view.php";
		exit();
	}

	function inputView(){
		$this->checkLoggedIn();
		if(!isset($_SESSION['matchs'])){
			echo "matchs no set";
			$matchs = $this->m_model->getMatchs();
			$_SESSION['matchs']=serialize($matchs);
			var_dump($_SESSION['matchs']);
		}
		$matchs = unserialize($_SESSION['matchs']);
		$pari = $this->paris_model->getParisbyParieur($_SESSION['Id_parieur']);		
		$parieur =  $this->p_model->getParieurInfobyID($_SESSION['Id_parieur']); 
		//var_dump($parieur->nom_parieur);
		$today_date=date("Y-m-d-H:i");
		$today_timestamp=strtotime($today_date);
		$endtimes = $this->m_model->getEndtimeInput();
		//var_dump($endtimes);
		foreach($endtimes as $endtime){
			$timestamp = strtotime($endtime->end_date);
			if($today_timestamp<$timestamp){
				$TypeNextPhase = $endtime->type_match;
				$res=$this->m_model->getMatchsIds($TypeNextPhase);
				//var_dump($res);
				$matchdebut = $res->{"min(id_match)"};
				$matchfin = $res->{"max(id_match)"};
				if($TypeNextPhase=="Poule"){
					$nombreMatchsPoule = $matchfin - $matchdebut+1;
				}
				break;
			}
		}
		//echo $TypeNextPhase;
		include "Views/header.php";
		include("Views/input_view.php");
		exit();
	}

	function simulationView(){
		$this->checkLoggedIn();
		//echo"avant";
		//var_dump(stripcslashes($_POST['groupe']));
		//var_dump(unserialize(stripcslashes($_POST['groupe'])));
		$matchs = $this->getMatchs();
		//var_dump($matchs);
		$nextGame=$this->m_model->getNextMatch();
		$TypeNextPhase = $nextGame->Type_match;
		$groupes = $this->m_model->getGroupes("Large");
		//var_dump($groupes);
		if (isset($_POST['groupe'])){
			$groupe = unserialize(stripcslashes($_POST['groupe']));
			if($groupe->name == "Poule"){
				$nombreMatchsPoule = $groupe->matchfin - $groupe->matchdebut+1;
			}
		}else{
			foreach ( $groupes as $element ) {
				if($element->name == "Poule"){
					$nombreMatchsPoule = $element->matchfin - $element->matchdebut+1;
				}	
		        if ( $TypeNextPhase == $element->name ) {
		            //echo $element->name;
		            $groupe = $element;
		            break;
		        }
	    	}
		}

	    //var_dump($matchdebut);
		//$pari = $this->paris_model->getParisbyParieur($_SESSION['Id_parieur']);		
		$parieur =  $this->p_model->getParieurInfobyID($_SESSION['Id_parieur']); 


		include "Views/header.php";
		include "Views/simulation_view.php";
		exit();
	}

	function statView(){	
		$matchs = $this->getMatchs();
		$parieurs = $this->p_model->getParieurs();
		$nextGame=$this->m_model->getNextMatch();
		$thisparieurID=$_SESSION['Id_parieur'];

		include "Views/header.php";
		include "Views/statistique_view.php";
		exit();
	}

	function updateSimulation(){
		$matchs = unserialize($_SESSION['matchs']);
		//var_dump($matchs);
		//var_dump($_POST);
		for($match = $_POST['matchdebut'];$match<=$_POST['matchfin'];$match++){
			$matchs[$match]->score_e1=intval($_POST['score_e1_m'.$match]);
			$matchs[$match]->score_e2=intval($_POST['score_e2_m'.$match]);
		}
		$_SESSION['matchs']=serialize($matchs);
		//var_dump($matchs);
		header('Location: table');
	}

	function validate($name,$email,$password,$repassword){

		//echo "validate all \n";
		
		//$new_parieur = new Parieur();
		
		$result_validate_name=$this->validate_name($name);
		if($result_validate_name === "error 201"){
			//echo "je devrais pas etre la";
			$message = "Please choose a Valid Username";
			$this->view("login",$message);
		}elseif($result_validate === "error 204"){
			$message = "Usermame already in use, please choose another one";
			$this->view("login",$message);
		}
		$result_validate_email = $this->validate_email($email);
		if($result_validate_email === "error 202"){
			$message = "Please choose a Valid Email";
			$this->view("login",$message);
		}
		$result_validate_password = $this->validate_password($password,$repassword);
		if($result_validate_password === "error 203"){
			$message = "Please confirm your password";
			$this->view("login",$message);
		}elseif($result_validate_password === true){
			//echo "name =".$name;
			$name=$this->nom_parieur;
			$email=$this->email;
			$password=$this->password;
			//echo "here";
			$res=$this->p_model->createNewParieur($name,$email,$password);
			//echo "here"
			$_SESSION['Id_parieur']=$res;
			header('Location: table');
		}else{
			echo"je devrais jamais etre ici validate else \n";
		}	
	}

	function getMatchs(){
		if(!isset($_SESSION['matchs'])){
			//echo "matchs no set";
			$matchs = $this->m_model->getMatchs();
			$_SESSION['matchs']=serialize($matchs);
		}
		//var_dump($_SESSION['matchs']);
		$matchs = unserialize($_SESSION['matchs']);

		return $matchs;
	}

	function validate_name($name){
		//echo"validate _name ".$name;
		$name=trim($name);

		//var_dump($this->$name);
		$this->nom_parieur=ucfirst(filter_var($name,FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
		//$this->name=ucfirst(filter_var($name,FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
		if(empty($this->nom_parieur)){
			//echo "false \n";
			return "error 201";
		}elseif ($this->parieurexist($name)) {
			//echo "false2 \n";
			return "error 204";
		}else{
			//echo "true \n";
			return true;
		}
	}

	function validate_email($email){
		//echo " la  validate email \n" ;
		$this->email=$email;
		$temp_email=filter_var($email,FILTER_VALIDATE_EMAIL);
		$this->email=filter_var($email,FILTER_SANITIZE_EMAIL);
		//var_dump ($this->$email);
		//var_dump ($temp_email);
		//$this->name=ucfirst(filter_var($name,FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
		if(empty($temp_email)){
			//echo "false";
			return "error 202";
		}else{
			//echo "true";
			$this->email=$temp_email;
			return true;
		}
	}

	function validate_password($password,$repassword){
		//echo"validate password \n";
		$p1 =trim($password);
		$p2 =trim($repassword);
		
		//var_dump ($this->$email);
		//var_dump ($temp_email);
		//$this->name=ucfirst(filter_var($name,FILTER_SANITIZE_STRING,FILTER_FLAG_STRIP_LOW));
		if(empty($p1)||empty($p2)||($p1!=$p2)){
			//echo "false";
			return "error 203";
		}else{
			$this->password = crypt($p1);
			return true;
		}
	}

	function parieurexist($name){
		//echo "</br> doudou </br>";
		//echo " parieur exixt start \n ";
	  	$result=$this->p_model->getParieurInfo($name);
	  	//echo "</br> rowcount".$result->rowcount();
	    if($result->rowcount()>0){
	    	return true;
	  	}else{
	    	return false;
	  	}
	}

	function login($name,$password){
		//echo "test login";
		//$existing_parieur = new Parieur();
		$res=$this->p_model->getparieurInfo($name);
		$existing_parieur=$res->fetch(PDO::FETCH_OBJ);
		//var_dump($existing_parieur);
		//verify password:
		$existing_parieur->password."\n";
		crypt($password,$existing_parieur->password)."\n";
		if(crypt($password,$existing_parieur->password)==$existing_parieur->password){
			$message = "password verified";
			foreach(get_object_vars($existing_parieur) as $key=>$value){
				$this->$key = $value;
			}
			$_SESSION['Id_parieur']=$this->id_parieur;
			header('Location: table');
		}else{
			$message = "please check password";
			$this->view("login",$message);
		}
		
		//var_dump($existing_parieur);
	}

	
	


}
//echo "debut";
//$parieur = new Parieur();
?>