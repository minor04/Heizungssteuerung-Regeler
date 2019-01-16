<?
class HeizungssteuerungRegler extends IPSModule
	{
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();	
			
			//___In_IPS_zurverfügungstehende_Variabeln_______________________________________________
			$this->RegisterVariableInteger("SWS", "Softwareschalter", "Heizung_SWS", 1);
			$this->RegisterVariableInteger("prog", "Programm", "Heizung_Programm", 2);
			$this->RegisterVariableFloat("SW", "Sollwert", "~Temperature.Room", 3);
			$this->RegisterVariableFloat("SW_Abs", "Sollwert Absenkung", "~Temperature.Difference", 4);
			$this->RegisterVariableFloat("SW_ber", "Sollwert Berechnet", "~Temperature.Room", 5);
			$this->RegisterVariableFloat("AT", "Aussentemperatur", "~Temperature", 6);
			$this->RegisterVariableFloat("AT_2h", "Aussentemperatur +2h", "~Temperature", 7);
			$this->RegisterVariableFloat("AT_4h", "Aussentemperatur +4h", "~Temperature", 8);
			$this->RegisterVariableFloat("AT_8h", "Aussentemperatur +8h", "~Temperature", 9);
			$this->RegisterVariableBoolean("ZP_Conf", "ZP_Confort", "~Switch", 11);
			$this->RegisterVariableBoolean("SWS_Abw", "Abwesenheit", "~Switch", 12);
			$this->RegisterVariableBoolean("Abw", "Abwesend", "~Switch", 15);
			
			//___Modulvariabeln______________________________________________________________________
			$this->RegisterPropertyInteger("SWS", 1);
			//$this->RegisterPropertyInteger("prog", 1);
			//$this->RegisterPropertyFloat("SW", 15);
			//$this->RegisterPropertyFloat("SW_Abs", 3);
			//$this->RegisterPropertyBoolean("ZP_Conf", true);
			//$this->RegisterPropertyBoolean("Abw", true);
			
			
			$this->RegisterPropertyInteger("UpdateWeatherInterval", 30);
			$this->RegisterPropertyString("APIkey", 0);
			$this->RegisterPropertyFloat("Lat", 0);
			$this->RegisterPropertyFloat("Long", 0);
			$this->RegisterPropertyBoolean("WetterForcast", true);
			
			
			//Timer erstellen
			$this->RegisterTimer("UpdateWeather", $this->ReadPropertyInteger("UpdateWeatherInterval"), 'WID_UpdateWeatherData($_IPS[\'TARGET\']);');
		}
	
	        public function ApplyChanges() {
            		//Never delete this line!
            		parent::ApplyChanges();
			
			//$sws = $this->ReadPropertyInteger("SWS");
			//$prog = $this->ReadPropertyInteger("prog");
			
			//Timerzeit setzen in Minuten
			$this->SetTimerInterval("UpdateWeather", $this->ReadPropertyInteger("UpdateWeatherInterval")*1000*60);
			

        	}
	
	        public function MessageSink ($TimeStamp, $SenderID, $Message, $Data) {
            		//$triggerID_01 = $this->ReadPropertyInteger("InputTriggerID_SWS");
	
			//if (($SenderID == $triggerID_01) && ($Message == 10603) && (boolval($Data[0]))){
				//$this->SWRegler();
           		//}
        }
        /**
        * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
        * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
        *
        * ABC_Calculate($id);
        *
        */
	
	public function RequestAction($key, $value){
		
        	switch ($key) {
        		case 'SWS':
				if ($value == 0){
					$Test = 10;					
				}
				else if ($value == 1){
					$Test = 20;	
				}
				else if ($value == 2){
					$Test = 30;	
				}
			//$sws = $this->ReadPropertyInteger("SWS");
			//$sws = GetValueInteger(28663);
			//echo $sws;
			//case 'ZP_Conf':
			//case 'Abw':
			//case 'SWS_Abw':
			$this->Test2();
	    		//$value = $value;
            	break;
        		case 'prog':
			case 'SW':
			case 'SW_Abs':
			$this->SWRegler();
            	break;
        	}
		
        $this->SetValue($key, $value);	
		
   	}
	
	public function UpdateWeatherData(){
		
		$apikey = $this->ReadPropertyString("APIkey");
		$lat = $this->ReadPropertyFloat("Lat");
		$long = $this->ReadPropertyFloat("Long");
		
			$url = "https://api.darksky.net/forecast/$apikey/$lat,$long?exclude=currently,minutely&lang=de&units=ca";	
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($ch, CURLOPT_URL, $url); 
			$output = curl_exec($ch);
			curl_close($ch);  
			$content = json_decode($output, true);
		
		SetValue($this->GetIDForIdent("AT"), $content['hourly']['data'][0]['temperature']);
		SetValue($this->GetIDForIdent("AT_2h"),$content['hourly']['data'][2]['temperature']);
		SetValue($this->GetIDForIdent("AT_4h"),$content['hourly']['data'][4]['temperature']);
		SetValue($this->GetIDForIdent("AT_8h"),$content['hourly']['data'][8]['temperature']);
		
		if ($this->ReadPropertyBoolean("WetterForcast")){
			IPS_SetHidden($this->GetIDForIdent("AT_2h"), false);
			IPS_SetHidden($this->GetIDForIdent("AT_4h"), false);
			IPS_SetHidden($this->GetIDForIdent("AT_8h"), false);
		}
		else{
			IPS_SetHidden($this->GetIDForIdent("AT_2h"), true);
			IPS_SetHidden($this->GetIDForIdent("AT_4h"), true);
			IPS_SetHidden($this->GetIDForIdent("AT_8h"), true);
		}
		
                	
		
	}
	
	public function VariabelProfilProgramm(){
		
		if (!IPS_VariableProfileExists("Heizung_Programm")) {
			
			IPS_CreateVariableProfile("Heizung_Programm", 1); // 0 boolean, 1 int, 2 float, 3 string,
			IPS_SetVariableProfileValues("Heizung_Programm", 1, 3, 1);
			IPS_SetVariableProfileDigits("Heizung_Programm", 0);
			IPS_SetVariableProfileAssociation("Heizung_Programm", 0, "Aus", "", 0xFFFFFF);
			IPS_SetVariableProfileAssociation("Heizung_Programm", 1, "Eco", "", 0xFFFFFF);
			IPS_SetVariableProfileAssociation("Heizung_Programm", 2, "Comfort", "", 0xFFFFFF);
			IPS_SetVariableProfileAssociation("Heizung_Programm", 3, "Abwesend", "", 0xFFFFFF);
		}
		else{
			echo "Das Variabelprofil". "'Heizung_Programm'". "ist bereits vorhanden";
		}
		
		if (!IPS_VariableProfileExists("Heizung_SWS")) {
			
			IPS_CreateVariableProfile("Heizung_SWS", 1); // 0 boolean, 1 int, 2 float, 3 string,
			IPS_SetVariableProfileValues("Heizung_SWS", 1, 2, 1);
			IPS_SetVariableProfileDigits("Heizung_SWS", 0);
			IPS_SetVariableProfileAssociation("Heizung_SWS", 0, "Aus", "", 0xFFFFFF);
			IPS_SetVariableProfileAssociation("Heizung_SWS", 1, "Ein", "", 0xFFFFFF);
			IPS_SetVariableProfileAssociation("Heizung_SWS", 2, "Auto", "", 0xFFFFFF);
		}
		else{
			echo "Das Variabelprofil". "'Heizung_SWS'". "ist bereits vorhanden";
		}
	}
	
	public function VariabelStandartaktion(){
		
		$this->EnableAction("SWS");
		$this->EnableAction("prog");
		$this->EnableAction("SW");
		$this->EnableAction("SW_Abs");
		

	}
		
	public function ZeitPro(){
		
		//if (!IPS_VariableProfileExists("Heizung_Programm")) {
			
			//IPS_CreateEvent(1); // 0 ausgelöst, 1 zyklisch, 2 Wochenpla
			//IPS_CreateVariableProfile("Hei", 1); // 0 ausgelöst, 1 zyklisch, 2 Wochenpla
			//$ScriptID = IPS_CreateScript(0);
			//IPS_SetName($ScriptID, "Zeitschaltprogramm");
			//IPS_SetParent($ScriptID, 14663);
		
			$KategorieID_Heizung = IPS_GetCategoryIDByName("Heizung", 0);
			$KategorieID_Settings = IPS_GetCategoryIDByName("Einstellungen", $KategorieID_Heizung);
			$InstanzID = IPS_GetInstanceIDByName("Regler", $KategorieID_Settings);
			
			$EreignisID =IPS_CreateEvent(2);
			IPS_SetName($EreignisID, "Zeitschaltprogramm");
			IPS_SetParent($EreignisID, $InstanzID);
			IPS_SetPosition($EreignisID, 10);
			
			IPS_SetEventScheduleGroup($EreignisID, 0, 31); //Mo - Fr (1 + 2 + 4 + 8 + 16)
			IPS_SetEventScheduleGroup($EreignisID, 1, 96); //Sa + So (32 + 64)
			
			IPS_SetEventScheduleAction($EreignisID, 0, "Eco", 0xFF8080, "SetValue(36402, false);");
			IPS_SetEventScheduleAction($EreignisID, 1, "Confort", 0xFF0000, "SetValue(36402, true);");
		
			IPS_SetEventScheduleGroupPoint($EreignisID, 0, 0, 0, 0, 0, 0); //Um 0:00 Aktion mit ID 0 "Eco" aufrufen
			IPS_SetEventScheduleGroupPoint($EreignisID, 0, 1, 6, 0, 0, 1); //Um 6:00 Aktion mit ID 1 "Comfort" aufrufen
			IPS_SetEventScheduleGroupPoint($EreignisID, 0, 2, 8, 0, 0, 0); //Um 8:00 Aktion mit ID 0 "Eco" aufrufen
			IPS_SetEventScheduleGroupPoint($EreignisID, 0, 3, 16, 0, 0, 1); //Um 16:00 Aktion mit ID 1 "Comfort" aufrufen
			IPS_SetEventScheduleGroupPoint($EreignisID, 0, 4, 22, 0, 0, 0); //Um 22:00 Aktion mit ID 0 "Eco" aufrufen
		
			IPS_SetEventScheduleGroupPoint($EreignisID, 1, 10, 0, 0, 0, 0); //Um 0:00 Aktion mit ID 0 "Eco" aufrufen
			IPS_SetEventScheduleGroupPoint($EreignisID, 1, 11, 7, 0, 0, 1); //Um 7:00 Aktion mit ID 1 "Comfort" aufrufen
			IPS_SetEventScheduleGroupPoint($EreignisID, 1, 12, 22, 0, 0, 0); //Um 22:00 Aktion mit ID 0 "Eco" aufrufen
		
		
			$EreignisID =IPS_CreateEvent(1);
			IPS_SetName($EreignisID, "Abwesend");
			IPS_SetParent($EreignisID, $InstanzID);
			IPS_SetPosition($EreignisID, 12);
			IPS_SetEventCyclic($EreignisID, 1 /* Täglich */ ,5,0,0,0,0);
			
			$EreignisID =IPS_CreateEvent(1);
			IPS_SetName($EreignisID, "Ankunft");
			IPS_SetParent($EreignisID, $InstanzID);
			IPS_SetPosition($EreignisID, 13);
			IPS_SetEventCyclic($EreignisID, 1 /* Täglich */ ,5,0,0,0,0);
		
			IPS_SetHidden($this->GetIDForIdent("ZP_Conf"), true);
			IPS_SetHidden($this->GetIDForIdent("Abw"), true);
		
		//}		
	
	}
		
	public function ProgrammAuswahl(){
		
		IPS_ApplyChanges(28875);
		
		//$sws = $this->ReadPropertyInteger("SWS");
		$sws = GetValueInteger(28663);
		//$sws = getValue($this->GetIDForIdent("SWS"));
		$zp_conf = getValue($this->GetIDForIdent("ZP_Conf"));
		$abw = getValue($this->GetIDForIdent("Abw"));
		$test = getValue($this->GetIDForIdent("SWS_Abw"));
		
		if($sws == 0){
			SetValue($this->GetIDForIdent("prog"), 0);
			IPS_SetDisabled($this->GetIDForIdent("prog"), false);
			//echo "0";
		}
		else if($sws == 1){
			IPS_SetDisabled($this->GetIDForIdent("prog"), false);
			//echo "1";
		}
		else{
			IPS_SetDisabled($this->GetIDForIdent("prog"), true);
			//echo "2";
			
			if($abw == true){
				SetValue($this->GetIDForIdent("prog"), 3);
				IPS_SetDisabled($this->GetIDForIdent("prog"), true);
			}
			else if($zp_conf == false){
				SetValue($this->GetIDForIdent("prog"), 1);
				IPS_SetDisabled($this->GetIDForIdent("prog"), true);
				//echo "Eco";
			}
			else{
				SetValue($this->GetIDForIdent("prog"), 2);
				IPS_SetDisabled($this->GetIDForIdent("prog"), true);
				//echo "Confort";
			}
		}
		
		
		$KategorieID_Heizung = IPS_GetCategoryIDByName("Heizung", 0);
		$KategorieID_Settings = IPS_GetCategoryIDByName("Einstellungen", $KategorieID_Heizung);
		$InstanzID = IPS_GetInstanceIDByName("Regler", $KategorieID_Settings);
		$VariabelID_Ab = IPS_GetEventIDByName("Abwesend", $InstanzID);
		$VariabelID_An = IPS_GetEventIDByName("Ankunft", $InstanzID);
		
				
		if($test == true){
			IPS_SetHidden($VariabelID_Ab, false);
			IPS_SetHidden($VariabelID_An, false);
		}
		else{
			IPS_SetHidden($VariabelID_Ab, true);
			IPS_SetHidden($VariabelID_An, true);
		}
		
		if($abw == false){
			SetValue($this->GetIDForIdent("SWS_Abw"), false);
		}
		
			$this->SWRegler();

	}
	
	public function SWRegler(){
		
		$program = $this->getValue("prog");
		//$program = $this->ReadPropertyInteger("prog");
		$sollwert = $this->getValue("SW");
		$sollwert_ab = $this->getValue("SW_Abs");
		
		$AT = $this->getValue("AT");
		$AT_2 = $this->getValue("AT_2h");
		$AT_4 = $this->getValue("AT_4h");
		$AT_8 = $this->getValue("AT_8h");
			

			//_________________Heizung_Eco__________________________________________________
			if ($program == 0) {
				$sollwert_ber = 0;
                	} 
			//_________________Heizung_Eco__________________________________________________
			if ($program == 1) {
				$sollwert_ber = ($sollwert - $sollwert_ab);
                	} 
			//_________________Heizung_Comfort______________________________________________ 
			else if ($program == 2) {
				//___Modul_Übergangszeit____________________________________________________
				if ($AT >= $sollwert || $AT_2 >= $sollwert || $AT_4 >= $sollwert || $AT_8 >= $sollwert){
					$sollwert_ber = ($sollwert - 2);
				}
				//___Modul_Frost____________________________________________________________
				else if ($AT <= -5){
					$sollwert_ber = $sollwert - (($AT + 5) * (-0.1));  //Schiebung über Vareabeln (((15-5)/100) * -1)
				}
				else{
					$sollwert_ber = ($sollwert);
				}
			}
			//_________________Heizung_Abwesend_____________________________________________ 
			else if ($program == 3) {
				$sollwert_ber = 18;
			}
		
		SetValue($this->GetIDForIdent("SW_ber"), $sollwert_ber);
         
	}
	
	public function Test(){
		
		//$this->EnableAction("SWS_Abw");
		//$test = $this->getValue("SW");
		$test = GetValueFloat(56821);
		//$test = $this->ReadPropertyFloat("SW");
		echo $test;
		

		
	}
	
	public function Test2(int $Test){
		//echo $Test;
				$this->EnableAction("test");
	
	}

	    

		   
    }
?>
