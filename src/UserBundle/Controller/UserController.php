<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
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
        
        $dql= "SELECT u FROM UserBundle:User u ORDER BY u.id DESC";
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
        //Se captura el valor de password.    
            $password = $form -> get('password') -> getData();
        //Valida que el usuario no se cree sin password.
            $passwordConstraint = new Assert\NotBlank();
            $errorList = $this->get('validator')->validate($password, $passwordConstraint);
        //En caso no hay error se procesa el password.
            if(count($errorList)==0)
            {
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
        //En caso de eror    
            else
            {
            // Mostrar error
                $errorMessage = new FormError($errorList[0]->getMessage());
                $form->get('password')->addError($errorMessage);
            }
        }
        
        return $this->render('UserBundle:User:add.html.twig', array('form'=> $form->createView()));
    }
    
    public function editAction($id)
    {
    //Cargar el controlador, must be refactor function.
        $em = $this-> getDoctrine() -> getManager();
    //Recuperar el registro del id
        $user = $em -> getRepository('UserBundle:User') -> find($id);
    //Manejando la excepcion de no encontrar el Id
        if(!$user)
        {
        //Cargar el traductor (debe estar in a refactor function)
            $messageException = $this->get('translator')->trans('User not found.'); 
            throw $this->createNotFoundException($messageException);
        }
    // Crear el formulario que sera enviado a la vista Editar
        $form = $this->createEditForm($user);
    // Renderizacion de la vista
        return $this->render('UserBundle:User:edit.html.twig', array('form'=> $form->createView()));
    }
    
    private function createEditForm(User $entity)
    {
    //se crea el formulario
        $form = $this->createForm(UserType::class, $entity, 
            array('action' => $this->generateUrl('user_update', array('id' => $entity->getId())), 'method' => 'PUT'));
            
        return $form;
    }
    
    public function updateAction($id, Request $request)
    {
    //Cargar el controlador, must be refactor function.    
        $em = $this-> getDoctrine() -> getManager();
    //Recuperar el registro del id
        $user = $em -> getRepository('UserBundle:User') -> find($id);
    //comprueba que el usuario exita    
        if(!$user)
        {
        //Cargar el traductor (must be a refactor function)
            $messageException = $this->get('translator')->trans('User not found.'); 
            throw $this->createNotFoundException($messageException);
        }
    //Se crea un formulario de edicion
        $form = $this->createEditForm($user);
    // procesando el formulario
        $form->handleRequest($request);
    //Validamos el envio del formulario y si los datos son correctos
        if($form->isSubmitted() && $form->isValid())
        {
        //Recuperamos el password
            $password = $form->get('password')->getData();
        //Verificamos si el usuario a ingresado un password nuevo
            if(!empty($password))
            {
            //Codificamos el nuevo password.(must be a refactor function)
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $password);
            //Se guarda el nuevo password en el arreglo de usuario.
                $user->setPassword($encoded);
            }
        //En caso que usuario no halla modificado sus password
            else
            {
                $recorverPass = $this->recoverPass($id);
                $user->setPassword($recorverPass[0]['password']);
            }
        //En caso se quiera modificar un Usuario Aministrador    
            if($form->get('role')->getData()=='ROLE_ADMIN')
            {
                $user->setIsActive(1);
            }
        //Guarda en la base de datos
            $em->flush();
        //Envia mensaje   
            $successMessage = $this->get('translator')->trans('User updated.');
            $this->addFlash('mensaje', $successMessage);
        //redigir a la vista edit mostrando el usuario ya editado.
            return $this->redirectToRoute('user_edit', array('id' => $user->getId()));
        }
    //En caso de error, redirigir a la vista Edit y cargar todo de nuevo.
        return $this->render('UserBundle:User:edit.html.twig', array('user' => $user, 'form' => $form->createView()));
    }
    
    private function recoverPass($id)
    {
    //Cargar el controlador, must be refactor function.    
        $em = $this-> getDoctrine() -> getManager();
    //Recuperar el password
        $query = $em->createQuery(
            'SELECT u.password
            FROM UserBundle:user u
            WHERE u.id = :id'    
        )->setParameter('id', $id);
        
        $currentPass = $query->getResult();
        
        return $currentPass;
    }
    
    public function viewAction($id)
    {
        $repository = $this -> getDoctrine() -> getRepository('UserBundle:User');
        $user =$repository ->find($id);
        
        return new Response('Usuario: ' . $user ->getUsername() . ' con email: ' . $user ->getEmail());
    }
}
