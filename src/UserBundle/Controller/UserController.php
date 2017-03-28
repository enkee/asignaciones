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
    public function homeAction()
    {
        //Redirigimos a nuestra vista home.
        return $this->render('UserBundle:User:home.html.twig');
    }
    
    public function indexAction(Request $request)
    {
        $searchQuery = $request->get('query');
        //Si hay contenido realizar la busqueda, caso contrario lo mismo.
        if(!empty($searchQuery))
        {
            /* A la fecha no hay compatibilidad con Symfony 3.2 y elasticaBundle
            //Craga el servicio de elasticasearch
            $finder = $this -> container->get('fos_elastica.finder.app.user');
            //Utiliza un metodo especial para adaptar los resultados al paginador
            $users = $finder ->createPaginatorAdapter($searchQuery);
            */
        }
        else{
            $em = $this-> getDoctrine() -> getManager();
            $dql= "SELECT u FROM UserBundle:User u ORDER BY u.id DESC";
            $users = $em->createQuery($dql);
        }
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $users, $request->query->getInt('page', 1),
            5
        );
        //Llamada al formulario de eliminacion AJAX
        $deleteFormAjax = $this -> createCustomForm(':USER_ID', 'DELETE', 'user_delete');
        //Llama a la vista index y envia todos los valores.
        return $this -> render('UserBundle:User:index.html.twig', array('pagination' => $pagination, 'delete_form_ajax' => $deleteFormAjax->createView()));
    }
    
    
    public function addAction()
    {
        
        $user = new User();
        //print('hola');
        //exit;
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
        //Carga el driver y selecciona el registro para su vista.
        $repository = $this -> getDoctrine() -> getRepository('UserBundle:User');
        $user =$repository ->find($id);
        //Manejando la excepcion de no encontrar el Id
        if(!$user)
        {
        //Cargar el traductor (debe estar in a refactor function)
            $messageException = $this->get('translator')->trans('User not found.'); 
            throw $this->createNotFoundException($messageException);
        }
        //Crea un formulario de eliminacion
        $deleteForm = $this->createCustomForm($user->getId(), 'DELETE', 'user_delete');
        //Visualiza la vista view y el formulario de eliminacion
        return $this->render('UserBundle:User:view.html.twig', array('user' => $user, 'delete_form' => $deleteForm->createView()));
    }
     
    public function deleteAction(Request $request, $id)
    {
        $em = $this-> getDoctrine()->getManager();
        $user = $em->getRepository('UserBundle:User')->find($id);
        
        if(!$user)
        {
        //Cargar el traductor (debe estar in a refactor function)
            $messageException = $this->get('translator')->trans('User not found.'); 
            throw $this->createNotFoundException($messageException);
        }
        //Sacamos total de registros especialmente para AJAX
        $allUsers = $em->getRepository('UserBundle:User')->findAll();
        $countUsers = count($allUsers);
        // $form = $this->createDeleteForm($user);
        $form = $this -> createCustomForm($user->getId(), 'DELETE', 'user_delete');
        $form -> handleRequest($request);
        //Validamos el envio del formulario
        if($form->isSubmitted() && $form->isValid())
        {
            //Si viene de AJAX
            if($request->isXMLHttpRequest())
            {
            //Creamos un metodo que elimine al usuario (refactored)
                $res = $this -> deleteUser($user->getRole(), $em, $user);
            //Retornamos el objeto Response a AJAX
                return new Response(
                    json_encode(array('removed' => $res['removed'], 'message' => $res['message'], 'countUsers' => $countUsers)),
                    //estado de la pagina
                    200,
                    //encabezado (formato de envio json)
                    array('Content-Type' => 'application/json')
                );
            }
        //Llamamos a una funcion que elimina al usuario.
            $res = $this->deleteUser($user->getRole(), $em, $user);
        //Devolvemos el mensaje de confirmacion.
            $this->addFlash($res['alert'],$res['message']);
        //redigir a la vista index mostrando el usuario ya eliminado.
            return $this->redirectToRoute('user_index'); 
        }
    }
    private function deleteUser($role, $em, $user)
    {
        //elimina solo usuarios tipo USER
        if($role == 'ROLE_USER')
        {
            $em->remove($user);
            $em->flush();
            //Envia mensaje de confirmacion, si se elimino el usuario
            $message = $this->get('translator')->trans('User deleted.');
            //Indica que fue eliminado.
            $removed = 1;
            //Indica el tipo de mensaje, eliminado o no eliminado.
            $alert = 'mensaje';
        }//si es de tipo ADMIN
        elseif($role == 'ROLE_ADMIN')
        {
            $message = $this->get('translator')->trans('This kind of user can not be deleted.');
            $removed = 0;
            $alert = 'error';
        }
        //retorna a la funcion principal con los valoes.
        return array('removed' => $removed, 'message' => $message, 'alert' => $alert);
    }
    //creacion del formulario de eliminacion (replaza a los demas)
    private function createCustomForm($id, $method, $route)
    {
        //creacion del formulario con sus metodos
        return $this->createFormBuilder()
            ->setAction($this->generateUrl($route, array('id'=>$id)))
            ->setMethod($method)
            ->getForm();
    }
}
