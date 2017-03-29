<?php

namespace UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response; 
use UserBundle\Entity\Task;
use UserBundle\Form\TaskType;

class TaskController extends Controller
{
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $dql = "SELECT t FROM UserBundle:Task t ORDER BY t.id DESC";
        $tasks = $em->createQuery($dql);
        
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $tasks,
            $request->query->getInt('page', 1),
            3
        );
        
        return $this->render('UserBundle:Task:index.html.twig', array('pagination' => $pagination));
    }
    
    public function customAction(Request $request)
    {
        //Recuperamos el id del usuario logeado, con el servicio de seguridad
        $idUser = $this->get('security.token_storage')->getToken()->getUser()->getId();
        //Recuperamos las tareas del usuario logeado, con dql doctrine.
        $em = $this->getDoctrine()->getManager();
        $dql = "SELECT t FROM UserBundle:Task t JOIN t.user u WHERE u.id = :idUser ORDER BY t.id DESC";
        $tasks = $em->createQuery($dql)->setParameter('idUser', $idUser);
        //Paginamos las tareas del usuario autenticado.
        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $tasks,
            $request->query->getInt('page', 1),
            3
        );
        //Creamos un formulario para procesar la tarea
        $updateForm = $this->createCustomForm(':TASK_ID', 'PUT', 'task_process');
        
        return $this->render('UserBundle:Task:custom.html.twig', array('pagination' => $pagination, 'update_form' => $updateForm->createView()));
    }
    
    public function processAction($id, Request $request)
    {
        $em =  $this->getDoctrine()->getManager();
        
        $task = $em->getRepository('UserBundle:Task')->find($id);
        
        if(!$task)
        {
            throw $this->createNotFoundException('The task not exist.');
        }
        //Armamos un nuevo formulario con los valores devueltos por el primer formulario 
        $form = $this->createCustomForm($task->getId(), 'PUT', 'task_process');
        $form->handleRequest($request);
        // si el formulario y valores no tuvo problemas para completarse entonces
        if($form->isSubmitted() && $form->isValid())
        {
            // se cambia el varlor de Status y se procesa en la base de datos
            if($task->getStatus()  == 0)
            {
                $task->setStatus(1);
                $em->flush();
                //Respondemos al metodo AJAX con formato json
                if($request->isXMLHttpRequest())
                {
                    return new Response(
                        json_encode(array('processed' => 1)),
                        200,
                        array('Content-Type' => 'application/json')
                    );
                }
            }
            // en caso la tarea ya hubiese sido procesada
            else
            {
                if($request->isXMLHttpRequest())
                {
                    return new Response(
                        json_encode(array('processed' => 0)),
                        200,
                        array('Content-Type' => 'application/json')
                    );
                }
            }
        }
    }
    
    public function addAction()
    {
        $task = new Task();
        $form = $this -> createCreateForm($task);
        
        return $this->render('UserBundle:Task:add.html.twig', array('form' => $form -> createView()));
    }
    
    public function createCreateForm(Task $entity)
    {
        $form = $this ->createForm(TaskType::class, $entity, array(
            'action' => $this->generateUrl('task_create'),
            'method' => 'POST'
        ));
        
        return $form;
    }
    
    public function createAction(Request $request)
    {
        $task = new Task();
        $form = $this->createCreateForm($task);
        $form->handleRequest($request);
        
        if($form->isValid())
        {
            $task->setStatus(0);
            $em = $this->getDoctrine()->getManager();
            $em->persist($task);
            $em->flush();
            
            $this->addFlash('mensaje', 'The task has been created.');
            return $this->redirectToRoute('task_index');
        }
        
        return $this->redirect('UserBundle:Task:add.html.twig', array('form' => $form->createView()));
    }
    
    public function viewAction($id)
    {
        //$em = $this->getDoctrine()->getManager();
        //exit($id);
        $task = $this->getDoctrine()->getRepository('UserBundle:Task')->find($id);
        //exit($task);
        if(!$task)
        {
            throw $this->createNotFoundException('The task not exist.');
            
        }
        
        $deleteForm = $this->createCustomForm($task->getId(), 'DELETE', 'task_delete');
        
        $user = $task->getUser();
        
        return $this->render('UserBundle:Task:view.html.twig', array('task' => $task, 'user' => $user, 'delete_form' => $deleteForm->createView()));
    }
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $task = $em->getRepository('UserBundle:Task')->find($id);
        
        if(!$task)
        {
            throw $this->createNotFoundException('The task not exist.');
            
        }
        //Cremos el formulario para editar la respectiva tarea.
        $form = $this->createEditForm($task);
        
        return $this->render('UserBundle:Task:edit.html.twig', array('task' => $task, 'form' => $form->createView()));
    }
    
    private function createEditForm(Task $entity)
    {
        $form = $this->createForm(TaskType::class, $entity, array(
            'action' => $this->generateUrl('task_update', array('id' => $entity->getId())),
            'method' => 'PUT'
        ));
        
        return $form;
    }
    
    public function updateAction($id, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $task = $em->getRepository('UserBundle:Task')->find($id);
        exit($task);
        if(!$task)
        {
            throw $this->createNotFoundException('The task not exist.');
        }
        
        $form = $this->createEditForm($task);
        //Procesar la peticion del formulario
        $form->handleRequest($request);
        
        if($form->isSubmitted() and $form->isValid())
        {
            $task->setStatus(0);
            $em->flush();
            $this->addFlash('mensaje', 'The task has been modified');
            return $this->redirectToRoute('task_edit', array('id' => $task->getId()));
            
        }
        
        return $this->render('UserBundle:Task:edit.html.twig', array('task' => $task, 'form' => $form->createView()));
    }
    
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $task = $em->getRepository('UserBundle:Task')->find($id);
        
         if(!$task)
        {
            throw $this->createNotFoundException('The task not exist.');
        } 
        
        $form = $this->createCustomForm($task->getId(),'DELETE', 'task_delete');
        //Procesar la peticion del formulario
        $form->handleRequest($request);
        
        if($form->isSubmitted() and $form->isValid())
        {
            $em->remove($task);
            $em->flush();
            $this->addFlash('mensaje', 'The task has been delited');
            return $this->redirectToRoute('task_index');
            
        }
        return $this->render('UserBundle:Task:edit.html.twig', array('task' => $task, 'form' => $form->createView()));
    }
    
    private function createCustomForm($id, $method, $route)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl($route, array('id' => $id)))
            ->setMethod($method)
            ->getForm();
    }
        
}
