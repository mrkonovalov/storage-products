<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ProductsController
 *
 * @package App\Controller
 *
 * @Route("/products")
 */
class ProductsController extends AbstractController
{
    /**
     * Гланая страница со скписком товаров
     *
     * @Route("/", name="products", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render(
            'products/index.html.twig',
            [
                'controller_name' => 'ProductsController',
            ]
        );
    }
    
    /**
     * Загрузка файла с товарами
     *
     * @Route("/upload", name="products_upload", methods={"POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function upload(Request $request): Response
    {
        $form = $this->makeForm();
        $form->handleRequest($request);
        
        //        dump($form->isValid(), $form); exit;
        //        dump(, $form->getErrors(true, false), $form->createView()); exit;
        if ($form->isSubmitted() && $form->isValid()) {
            // the data is an *array* containing email and siteUrl
            $data = $form->getData();
            
            
            return $this->redirectToRoute('products');
        }
        
        return $this->render(
            'home/index.html.twig',
            [
                'form'            => $form->createView(),
                'controller_name' => 'ProductsController',
            ]
        );
    }
    
    /**
     * Отрисовка формы загрузки файла
     *
     * @param \Symfony\Component\Form\FormView|null $form
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderForm(FormView $form = null): Response
    {
        return $this->render(
            'products/upload_form.html.twig',
            [
                'form' => $form ?: $this->makeForm()->createView(),
            ]
        );
    }
    
    /**
     * Построитель формы
     */
    private function makeForm(): FormInterface
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('products_upload'))
            ->setMethod('POST')
            ->add(
                'products_file',
                FileType::class,
                [
                    'label'       => 'Файл с товарами',
                    'required'    => true,
                    'constraints' => [
                        new File(
                            [
                                'maxSize'   => '30M',
                                'mimeTypes' => [
                                    'application/*',
                                    'text/plain',
                                ],
                            
                            ]
                        ),
                        new NotBlank(),
                    ],
                ]
            )
            ->add(
                'save',
                SubmitType::class,
                [
                    'label' => 'Загрузить',
                ]
            )
            ->getForm();
    }
}
