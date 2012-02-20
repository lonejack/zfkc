<?php
class Application_Form_Register extends Zend_Form
{
    private $elementDecorators = array(
        'ViewHelper',
        array(array('data' => 'HtmlTag'), array('tag' => 'div', 'class' => 'element')),
        'Label',
        array(array('row' => 'HtmlTag'), array('tag' => 'li')),
    );
 
    private $buttonDecorators = array(
        'ViewHelper',
        array(array('data' => 'HtmlTag'), array('tag' => 'div', 'class' => 'button')),
        array(array('row' => 'HtmlTag'), array('tag' => 'li')),
    );
 
    public function init()
    {
        $this->setMethod('post');
 
        $firstName = new Zend_Form_Element_Text('first_name', array(
            'decorators' => $this->elementDecorators,
            'label' => 'First name',
            'required' => true,
            'filters' => array(
                'StringTrim'
            ),
            'validators' => array(
                array('StringLength', false, array(2, 50))
            ),
            'class' => 'input-text'
        ));
 
        $lastName = new Zend_Form_Element_Text('last_name', array(
            'decorators' => $this->elementDecorators,
            'label' => 'First name',
            'required' => true,
            'filters' => array(
                'StringTrim'
            ),
            'validators' => array(
                array('StringLength', false, array(2, 50))
            ),
            'class' => 'input-text'
        ));
 
        $email = new Zend_Form_Element_Text('email', array(
            'decorators' => $this->elementDecorators,
            'label' => 'Email',
            'required' => true,
            'filters' => array(
                'StringTrim'
            ),
            'validators' => array(
                'EmailAddress'
            ),
            'class' => 'input-text'
        ));
 
        $emailAgain = new Zend_Form_Element_Text('emailAgain', array(
            'decorators' => $this->elementDecorators,
            'label' => 'Email again',
            'required' => true,
            'filters' => array(
                'StringTrim'
            ),
            'validators' => array(
                'EmailAddress'
            ),
            'class' => 'input-text'
        ));
 
        $username = new Zend_Form_Element_Text('username', array(
            'decorators' => $this->elementDecorators,
            'label' => 'Username',
            'required' => true,
            'filters' => array(
                'StringTrim'
            ),
            'validators' => array(
                array('StringLength', false, array(3, 50))
            ),
            'class' => 'input-text'
        ));
 
        $password = new Zend_Form_Element_Password('password', array(
            'decorators' => $this->elementDecorators,
            'label' => 'Password',
            'required' => true,
            'filters' => array(
                'StringTrim'
            ),
            'validators' => array(
                array('StringLength', false, array(6, 50))
            ),
            'class' => 'input-password'
        ));
 
        $passwordAgain = new Zend_Form_Element_Password('passwordAgain', array(
            'decorators' => $this->elementDecorators,
            'label' => 'Password again',
            'required' => true,
            'filters' => array(
                'StringTrim'
            ),
            'validators' => array(
                array('StringLength', false, array(6, 50))
            ),
            'class' => 'input-password'
        ));
 
        $submit = new Zend_Form_Element_Submit('register', array(
            'decorators' => $this->buttonDecorators,
            'label' => 'Register',
            'class' => 'input-submit'
        ));
 
        $this->addElements(array(
            $firstName,
            $lastName,
            $email,
            $emailAgain,
            $username,
            $password,
            $passwordAgain,
            $submit
        ));
    }
 
    public function loadDefaultDecorators()
    {
        $this->setDecorators(array(
            'FormErrors',
            'FormElements',
            array('HtmlTag', array('tag' => 'ol')),
            'Form'
        ));
    }
}