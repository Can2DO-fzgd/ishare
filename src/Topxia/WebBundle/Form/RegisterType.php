<?php

namespace Topxia\WebBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RegisterType extends AbstractType
{
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('userTypeId', 'text');
		$builder->add('email', 'text');
        $builder->add('password', 'password');
        $builder->add('confirmPassword', 'password');
        $builder->add('userName', 'text');
    }

    public function getName()
    {
    	return 'register';
    }
}
