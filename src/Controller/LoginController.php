<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use App\Entity\Registeration;
use App\Form\RegisterationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginController extends AbstractController
{
    private $session; 
    private $passwordEncoder;
  

    public function __construct(SessionInterface $session,UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->session = $session;
        $this->passwordEncoder = $passwordEncoder;
    }
    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        return $this->render('/index.html.twig');
    }
    public function login($msg=null)
    {
        $this->session->remove('email');
        return $this->render('/login.html.twig', [
            'messages'=>$msg
        ]);
    }
    public function loginAction(Request $request)
    {
        $registeration = new Registeration();
        $username = $request->get('username','');
        $password = $request->get('password');
        // $password = password_hash($password, PASSWORD_BCRYPT);
        // $password = $this->passwordEncoder->encodePassword($registeration,$password);
        
  
        $repository = $this->getDoctrine()->getRepository(Registeration::class);
        $data = $repository->findOneBy([
            'email' => $username,
        ]);
       
        // $salt = $data->getSalt();
        if($data)
        {
            $pass = $data->getPassword();

            if (password_verify($password, $pass))
            {
                $this->session->set('email', $data->getEmail());
                $role = $data->getRoles();
                $name = $data->getName();
                $email = $data->getEmail();
                if($role=='Admin')
                {
                    return $this->admin($name);
                }
                else
                {
                    return $this->render('/user.html.twig', [
                        'fullname'=>$name, 'email'=>$email
                    ]);
                }
            }
            else
            {
                $msg = "Invalid Password...!!!";
                return $this->login($msg);
            }
        
        }
        else 
        { 
            $msg = "Invalid Login Username...!!!";
           return $this->login($msg);
        }
        
    }
    public function admin($name=null)
    {
        
        $foo = $this->session->get('email', []);
        if($this->session->get('email'))
        {
            
            return $this->render('/admin.html.twig',[
                'name'=>$name
            ]);
        }
        else
        {
            return $this->login();
        }
    }
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordEncoderInterface $passwordEncoder)
    {
        $msg="";
        $registeration = new Registeration();
        $form = $this->createForm(RegisterationFormType::class, $registeration);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid()) {
            $password = $form["password"]->getData();
            // $password = password_hash($password, PASSWORD_BCRYPT);
            // $registeration->setPassword($password);
            $registeration->setPassword($this->passwordEncoder->encodePassword($registeration,$password));
            $entityManager->persist($registeration);
            $entityManager->flush();
            $msg='Registered Successfully...!!';
        }
        return $this->render('/register.html.twig', [
        'register_form'=> $form->createView(), 'message'=>$msg
            ]);
    }
}
