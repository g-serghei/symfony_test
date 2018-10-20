<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/product")
 */
class ProductController extends AbstractController
{
    /**
     * @Route("/", name="product_index", methods="GET")
     */
    public function index(ProductRepository $productRepository, Request $request): Response
    {
        $keywords = $request->query->get('keywords', null);
        if ($keywords) {
            $products = $productRepository->searchByKeywords($keywords);
        } else {
            $products = $productRepository->findAll();
        }

        return $this->render('product/index.html.twig', ['products' => $products]);
    }

    /**
     * @Route("/new", name="product_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $form['image']->getData();

            $fileName = md5(uniqid()) . '.' . $file->guessExtension();

            try {
                $file->move(
                    $this->getParameter('product_images_path'),
                    $fileName
                );
            } catch (FileException $e) {
                throw $e;
            }

            $product->setImage($fileName);

            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();

            return $this->redirectToRoute('product_index');
        }

        return $this->render('product/new.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="product_show", methods="GET")
     */
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', ['product' => $product]);
    }

    /**
     * @Route("/{id}/edit", name="product_edit", methods="GET|POST")
     */
    public function edit(Request $request, Product $product, Filesystem $fileSystem): Response
    {
        $currentImageName = $product->getImage();
        $currentImagePath = $this->getParameter('product_images_path') . '/' . $currentImageName;
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
            $file = $form['image']->getData();
            if ($file instanceof UploadedFile) {
                $fileName = md5(uniqid()) . '.' . $file->guessExtension();

                try {
                    if (is_file($currentImagePath)) {
                        $fileSystem->remove($currentImagePath);
                    }

                    $file->move(
                        $this->getParameter('product_images_path'),
                        $fileName
                    );
                } catch (FileException $e) {
                    throw $e;
                }

                $product->setImage($fileName);
            } else {
                $product->setImage($currentImageName);
            }

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('product_index');
        }


        return $this->render('product/edit.html.twig', [
            'product' => $product,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="product_delete", methods="DELETE")
     */
    public function delete(Request $request, Product $product, Filesystem $fileSystem): Response
    {
        if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->request->get('_token'))) {
            $currentImagePath = $this->getParameter('product_images_path') . '/' . $product->getImage();

            $em = $this->getDoctrine()->getManager();
            $em->remove($product);
            $em->flush();

            if (is_file($currentImagePath)) {
                $fileSystem->remove($currentImagePath);
            }
        }

        return $this->redirectToRoute('product_index');
    }
}
