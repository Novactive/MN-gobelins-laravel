<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;
use Faker;

class CategoryFixtures extends Fixture
{
    private int $counter = 1;
    /**
     * @param ObjectManager $manager
     * @return void
     */
    public function load(ObjectManager $manager): void
    {
        $parent = $this->createCategory('A Louer', $manager);

        $this->createCategory('Machine A', $manager, $parent);
        $this->createCategory('Comion', $manager, $parent);
        $this->createCategory('Chariot', $manager, $parent);

        $parent = $this->createCategory('A Vendre', $manager);

        $this->createCategory('Plafon', $manager, $parent);
        $this->createCategory('Bois', $manager, $parent);
        $this->createCategory('Carreaux', $manager, $parent);

        $manager->flush();
    }

    /**
     * @param string $name
     * @param ObjectManager $manager
     * @param Category|null $parent
     * @return Category
     */
    public function createCategory(string $name, ObjectManager $manager, Category $parent = null): Category
    {
        // use the factory to create a Faker\Generator instance
        $faker = Faker\Factory::create('fr_FR');

        $category = new Category();
        $category->setName($name);
        $category->setDescription($faker->text(25));
        $category->setParent($parent);
        $manager->persist($category);

        $this->addReference('cat-' . $this->counter, $category);
        $this->counter++;

        return $category;
    }
}
