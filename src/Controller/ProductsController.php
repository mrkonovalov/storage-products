<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\FileUploaderProducts;
use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
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
        $finder = new Finder();
        $finder->files()->in($this->getParameter('products_storage_files_dir'));
        
        $files = [];
        if ($finder->hasResults()) {
            foreach ($finder as $file) {
                $files[] = [
                    'name' => $file->getBasename(),
                    'link' => $this->generateUrl(
                        'products_show',
                        [
                            'file_name' => $file->getBasename(),
                        ]
                    ),
                ];
            }
        }
        
        return $this->render(
            'products/index.html.twig',
            [
                'files' => $files,
            ]
        );
    }
    
    /**
     * Просмотр содержимого файла
     *
     * @Route("/show/{file_name}", name="products_show", methods={"GET"})
     *
     * @param $file_name
     * @return Response
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    public function show($file_name): Response
    {
        $file_path    = $this->getParameter('products_storage_files_dir').'/'.$file_name;
        $file         = new SplFileInfo(
            $file_path,
            $this->getParameter('products_storage_files_dir'),
            $this->getParameter('products_storage_files_dir')
        );
        $file_content = $file->getContents();
        
        // Решил делать через сериализацию и через объекты:
        // 1. Не нужно писать для основыных форматов файлов (xml,csv,json) свои парсеры.
        // А если понадобиться другой формат, то можно просто добавить Encoder
        //
        // 2. Можно написать ноализатор, кторый бы преобразовывал данные в нужный формат
        // 3. Если захочется записывать данные в БД, то всё просто - пол дела уже готово.
        // 4. В класс Product можно добавлять свои методы, используемые в разных местах системы
        
        // TOTO:: пустую строку парсить не умеет
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $encoders             = [new XmlEncoder(), new JsonEncode(), new CsvEncoder(';')];
        $normalizers          = [new ObjectNormalizer($classMetadataFactory), new ArrayDenormalizer()];
        $serializer           = new Serializer($normalizers, $encoders);
        
        $error_read = false;
        $products   = [];
        try {
            $products = $serializer->deserialize(
                $file_content,
                Product::class.'[]',
                $file->getExtension(),
                [
                    'allow_extra_attributes' => false,
                ]
            );
        } catch (\Exception $exception) {
            $error_read = true;
        }
        
        return $this->render(
            'products/show.html.twig',
            [
                'file_name' => $file_name,
                'error'     => $error_read ? "Не удалось прочитать файл {$file_name} =((" : null,
                'products'  => $products,
            ]
        );
    }
    
    /**
     * Загрузка файла с товарами
     *
     * @Route("/upload", name="products_upload", methods={"POST"})
     *
     * @param Request              $request
     * @param FileUploaderProducts $fileUploader
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function upload(Request $request, FileUploaderProducts $fileUploader): Response
    {
        $form = $this->makeForm();
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // the data is an *array* containing email and siteUrl
            $data      = $form->getData();
            $file_data = $data['products_file'];
            
            $fileName = $fileUploader->upload($file_data);
            
            return $this->redirectToRoute('products_show', [
                'file_name' => $fileName
            ]);
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
