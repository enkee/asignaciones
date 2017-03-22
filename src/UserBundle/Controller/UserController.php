<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UserBundle\Entity\User;
use UserBundle\Form\UserType;

class UserController extends Controller
{
    public function indexAction(Request $request)
    {
        $em = $this-> getDoctrine() -> getManager();
        // $users = $em -> getRepository('UserBundle:User') -> findAll();
        
        /*
        $res = 'Lista de Usuarios: </br>';
        foreach($users as $user)
        {
            $res .= 'Usuario: ' . $user->getUsername() . ' - Email: ' . $user->getEmail() . '</br>';
        }
        return new Response($res);
        */
        
        $dql= "SELECT u FROM UserBundle:User u";
        $users = $em->createQuery($dql);
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $users, $request->query->getInt('page', 1),
            10
        );
        
        return $this -> render('UserBundle:User:index.html.twig', array('pagination' => $pagination));
        
    }
    
    public function addAction()
    {
        $user = new User();
        $form = $this->createCreateForm($user);
        
        return $this->render('UserBundle:User:add.html.twig', array('form'=> $form->createView()));
    }
    
    /*Este metodo sirve para estructurar el formulario*/
    private function createCreateForm(User $entity)
    {
        $form = $this->createForm(UserType::class, $entity, array(
                'action' => $this->generateUrl('user_create'),
                'method' => 'POST'
            ));
            
        return $form;
    }
    
    /* Procesa el formulario y guarda en la base de datos*/
    public function createAction(Request $request)
    {
        $user = new User();
        $form = $this->createCreateForm($user);
        $form->handleRequest($request);
        
        if($form->isValid())
        {
            
            $password = $form -> get('password') -> getData();
            
            $encoder = $this -> container -> get('security.password_encoder');
            $encoded = $encoder -> encodePassword($user, $password);
            
            $user -> setPassword($encoded);
            
            
            $em = $this -> getDoctrine() -> getManager();
            $em -> persist($user);
            $em -> flush();
            
            $successMessage= $this->get('translator')->trans('The user has been created.');
            $this->addFlash('mensaje', $successMessage);
            
            
            return $this->redirectToRoute('user_index');
        }
        
        return $this->render('UserBundle:User:add.html.twig', array('form'=> $form->createView()));
    }
    
    public function viewAction($id)
    {
        $repository = $this -> getDoctrine() -> getRepository('UserBundle:User');
        $user =$repository ->find($id);
        
        return new Response('Usuario: ' . $user ->getUsername() . ' con email: ' . $user ->getEmail());
    }
    

}
