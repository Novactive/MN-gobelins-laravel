<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Products;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;
use Faker;

class ProductFixtures extends Fixture
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }
    public function load(ObjectManager $manager): void
    {
        // use the factory to create a Faker\Generator instance
        $faker = Faker\Factory::create('fr_FR');

        for ($prod = 1; $prod <= 20; $prod++) {
            $product = new Product();
            $product->setName($faker->text(15));
            $product->setSlug($this->slugger->slug($product->getName()));
            $product->setDescription($faker->text());
            $product->setPrice($faker->numberBetween(900, 150000));
            $product->setStock($faker->numberBetween(0, 10));

            //On va chercher une référence de catégorie
            $category = $this->getReference('cat-' . rand(1, 8));
            $product->setCategories($category);

            $this->setReference('prod-' . $prod, $product);
            $manager->persist($product);
        }

        $manager->flush();
    }
}
