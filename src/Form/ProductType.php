<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('image', FileType::class, ['required' => false])
            ->add('price')
            ->add('category')
        ;

        $builder->get('image')
            ->addModelTransformer(new CallbackTransformer(
                function ($image) {
                    return empty($image) ? '' : new File($this->container->getParameter('product_images_path') . '/' . $image);
                },
                function ($image) use ($builder) {
                    return empty($image) ? $this->container->getParameter('product_images_path') . '/' . $builder->getData()->getImage() : $image;
                }
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
