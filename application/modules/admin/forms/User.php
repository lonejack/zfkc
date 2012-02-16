<?php

class Admin_Form_User extends My_Form_Pcl
{
    public function removeAntiCsrf()
    {
    	$this->removeElement('anti_csrf_admin');
    	return ;
    }
    
	public function ifValidRemoveHash($data)
    {
    	$ret = $this->isValid($data);
    	if($ret)
    		$this->removeAntiCsrf();
    	return $ret;
    }
    
    public function init()
    {
        /* Form Elements & Other Definitions Here ... */
    	$this->setElementsBelongTo('user');
    	$this->addElement('hash', 'anti_csrf_admin', array('salt' => 'unique'));
        $this->addElement('text', 'id', array(
            'label'    => 'id: ',
            'required' => true,
        ));
        $this->addElement('text', 'username', array(
            'label'    => 'Username: ',
            'required' => true
        ));
        $this->addElement('text', 'first_name', array(
            'label'    => 'First name: ',
            'required' => true
        ));
        $this->addElement('text', 'last_name', array(
            'label'    => 'Last name: ',
            'required' => true
        ));
        $this->addElement('text', 'address', array(
            'label'    => 'indirizzo: ',
            'required' => true
        ));

        $provincia = new Zend_Form_Element_Select('province', array(
            'label'    => 'Provincia: ',
            'required' => true,
        ));
        
        $provincia->addMultiOptions(array(
			'AG'	=>	'Agrigento',
			'AL'	=>	'Alessandria',
			'AN'	=>	'Ancona',
			'AO'	=>	'Aosta',
			'AR'	=>	'Arezzo',
			'AP'	=>	'Ascoli Piceno',
			'AT'	=>	'Asti',
			'AV'	=>	'Avellino',
			'BA'	=>	'Bari',
			'BT'	=>	'Barletta-Andria-Trani',
			'BL'	=>	'Belluno',
			'BN'	=>	'Benevento',
			'BG'	=>	'Bergamo',
			'BI'	=>	'Biella',
			'BO'	=>	'Bologna',
			'BZ'	=>	'Bolzano',
			'BS'	=>	'Brescia',
			'BR'	=>	'Brindisi',
			'CA'	=>	'Cagliari',
			'CL'	=>	'Caltanissetta',
			'CB'	=>	'Campobasso',
			'CI'	=>	'Carbonia-Iglesias',
			'CE'	=>	'Caserta',
			'CT'	=>	'Catania',
			'CZ'	=>	'Catanzaro',
			'CH'	=>	'Chieti',
			'CO'	=>	'Como',
			'CS'	=>	'Cosenza',
			'CR'	=>	'Cremona',
			'KR'	=>	'Crotone',
			'CN'	=>	'Cuneo',
			'EN'	=>	'Enna',
			'FM'	=>	'Fermo',
			'FE'	=>	'Ferrara',
			'FI'	=>	'Firenze',
			'FG'	=>	'Foggia',
			'FC'	=>	'Forlì-Cesena',
			'FR'	=>	'Frosinone',
			'GE'	=>	'Genova',
			'GO'	=>	'Gorizia',
			'GR'	=>	'Grosseto',
			'IM'	=>	'Imperia',
			'IS'	=>	'Isernia',
			'SP'	=>	'La Spezia',
			'AQ'	=>	'L\'Aquila',
			'LT'	=>	'Latina',
			'LE'	=>	'Lecce',
			'LC'	=>	'Lecco',
			'LI'	=>	'Livorno',
			'LO'	=>	'Lodi',
			'LU'	=>	'Lucca',
			'MC'	=>	'Macerata',
			'MN'	=>	'Mantova',
			'MS'	=>	'Massa-Carrara',
			'MT'	=>	'Matera',
			'ME'	=>	'Messina',
			'MI'	=>	'Milano',
			'MO'	=>	'Modena',
			'MB'	=>	'Monza e della Brianza',
			'NA'	=>	'Napoli',
			'NO'	=>	'Novara',
			'NU'	=>	'Nuoro',
			'OT'	=>	'Olbia-Tempio',
			'OR'	=>	'Oristano',
			'PD'	=>	'Padova',
			'PA'	=>	'Palermo',
			'PR'	=>	'Parma',
			'PV'	=>	'Pavia',
			'PG'	=>	'Perugia',
			'PU'	=>	'Pesaro e Urbino',
			'PE'	=>	'Pescara',
			'PC'	=>	'Piacenza',
			'PI'	=>	'Pisa',
			'PT'	=>	'Pistoia',
			'PN'	=>	'Pordenone',
			'PZ'	=>	'Potenza',
			'PO'	=>	'Prato',
			'RG'	=>	'Ragusa',
			'RA'	=>	'Ravenna',
			'RC'	=>	'Reggio Calabria',
			'RE'	=>	'Reggio Emilia',
			'RI'	=>	'Rieti',
			'RN'	=>	'Rimini',
			'RM'	=>	'Roma',
			'RO'	=>	'Rovigo',
			'SA'	=>	'Salerno',
			'VS'	=>	'Medio Campidano',
			'SS'	=>	'Sassari',
			'SV'	=>	'Savona',
			'SI'	=>	'Siena',
			'SR'	=>	'Siracusa',
			'SO'	=>	'Sondrio',
			'TA'	=>	'Taranto',
			'TE'	=>	'Teramo',
			'TR'	=>	'Terni',
			'TO'	=>	'Torino',
			'OG'	=>	'Ogliastra',
			'TP'	=>	'Trapani',
			'TN'	=>	'Trento',
			'TV'	=>	'Treviso',
			'TS'	=>	'Trieste',
			'UD'	=>	'Udine',
			'VA'	=>	'Varese',
			'VE'	=>	'Venezia',
			'VB'	=>	'Verbano-Cusio-Ossola',
			'VC'	=>	'Vercelli',
			'VR'	=>	'Verona',
			'VV'	=>	'Vibo Valentia',
			'VI'	=>	'Vicenza',
			'VT'	=>	'Viterbo'        
        ));
        $this->addElement($provincia);
        
        $this->addElement('text', 'zip', array(
            'label'    => 'CAP: ',
            'required' => true
        ));
        $email = new Zend_Form_Element_Text('email', array(
            'label'    => 'email: ',
            'required' => true,
        ));
        $email->addValidator(new Zend_Validate_EmailAddress());
        $this->addElement($email);
        
        $role = new Zend_Form_Element_Select('role', array(
            'label'    => 'User role: ',
            'required' => true,
        ));
        $role->addMultiOptions(array(
        	'guest'			=> 'guest',
        	'member'		=> 'member',
        	'admin'			=> 'admin',
        	'superadmin'	=> 'superadmin'));
        $this->addElement($role);
        
        $this->addElement('text', 'url', array(
            'label'    => 'URL website: ',
            'required' => false
        ));
        $this->addElement('text', 'download_area', array(
            'label'    => 'Download Area: ',
            'required' => true
        ));
        $this->setAttrib('accept-charset', 'utf-8');
    }
}

