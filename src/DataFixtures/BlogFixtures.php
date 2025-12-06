<?php

namespace App\DataFixtures;

use App\Entity\BlogCategory;
use App\Entity\BlogPost;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class BlogFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private SluggerInterface $slugger
    ) {
    }

    public static function getGroups(): array
    {
        return ['blog'];
    }

    public function load(ObjectManager $manager): void
    {
        // Créer des catégories
        $categories = [
            ['name' => 'Actualités', 'color' => '#3b82f6'],
            ['name' => 'Guides', 'color' => '#10b981'],
            ['name' => 'Dégustations', 'color' => '#f59e0b'],
            ['name' => 'Histoire', 'color' => '#8b5cf6'],
        ];

        $categoryEntities = [];
        foreach ($categories as $cat) {
            $category = new BlogCategory();
            $category->setName($cat['name']);
            $category->setSlug($this->slugger->slug($cat['name'])->lower());
            $category->setColor($cat['color']);
            $manager->persist($category);
            $categoryEntities[] = $category;
        }

        // Récupérer un utilisateur admin ou le premier utilisateur
        $admin = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@rhum-shop.com']);
        
        if (!$admin) {
            $admin = $manager->getRepository(User::class)->findOneBy([]);
        }

        if (!$admin) {
            echo "⚠️ Aucun utilisateur trouvé. Veuillez d'abord créer un utilisateur.\n";
            return;
        }

        // Créer des articles
        $posts = [
            [
                'title' => 'Les origines du rhum : un voyage dans le temps',
                'excerpt' => 'Découvrez l\'histoire fascinante du rhum, de ses origines caribéennes à sa popularité mondiale.',
                'content' => "Le rhum, cette boisson spiritueuse qui évoque les îles tropicales et les pirates, possède une histoire riche et fascinante qui remonte au XVIIe siècle.\n\nTout a commencé dans les plantations de canne à sucre des Caraïbes, où les esclaves découvrirent que la mélasse, un sous-produit de la production de sucre, pouvait être fermentée et distillée pour créer une boisson alcoolisée puissante.\n\nAu fil des siècles, le rhum est devenu une partie intégrante de la culture caribéenne et un élément important du commerce triangulaire. Aujourd'hui, chaque île des Caraïbes a développé son propre style de rhum, avec des méthodes de production et des saveurs uniques.",
                'category' => 3, // Histoire
            ],
            [
                'title' => 'Comment déguster un rhum comme un expert',
                'excerpt' => 'Apprenez les techniques de dégustation professionnelle pour apprécier pleinement les arômes et saveurs de votre rhum.',
                'content' => "La dégustation du rhum est un art qui nécessite de la pratique et de l'attention.\n\nCommencez par observer la robe du rhum : sa couleur peut vous donner des indices sur son âge et son mode de vieillissement. Ensuite, humez délicatement le rhum en plusieurs fois, en laissant votre nez s'habituer aux vapeurs d'alcool.\n\nLors de la mise en bouche, prenez une petite gorgée et laissez le rhum enrober votre palais. Essayez d'identifier les différentes notes : fruits, épices, boisé, caramel... L'idéal est de déguster le rhum pur d'abord, puis avec quelques gouttes d'eau pour libérer certains arômes.",
                'category' => 2, // Dégustations
            ],
            [
                'title' => 'Les différents types de rhum expliqués',
                'excerpt' => 'Rhum blanc, rhum ambré, rhum vieux... Découvrez les caractéristiques de chaque type de rhum.',
                'content' => "Il existe trois grandes catégories de rhum, chacune avec ses particularités.\n\nLe rhum blanc (ou rhum agricole blanc) est non vieilli ou très peu vieilli. Il conserve des arômes frais de canne à sucre et est parfait pour les cocktails.\n\nLe rhum ambré a vieilli quelques années en fût, ce qui lui donne une couleur dorée et des arômes plus complexes. Il peut se déguster pur ou en cocktail.\n\nLe rhum vieux a passé au moins trois ans en fût de chêne. Il développe des arômes profonds de vanille, de caramel et d'épices. C'est le rhum de dégustation par excellence.",
                'category' => 1, // Guides
            ],
            [
                'title' => 'Top 5 des cocktails au rhum à essayer absolument',
                'excerpt' => 'Mojito, Piña Colada, Ti-Punch... Découvrez les cocktails incontournables à base de rhum.',
                'content' => "Le rhum est l'ingrédient principal de nombreux cocktails emblématiques.\n\n1. Le Mojito : frais et mentholé, originaire de Cuba\n2. La Piña Colada : crémeuse et tropicale, de Porto Rico\n3. Le Ti-Punch : simple et puissant, des Antilles françaises\n4. Le Daiquiri : élégant et équilibré, de Cuba\n5. Le Mai Tai : complexe et fruité, de Polynésie\n\nChacun de ces cocktails met en valeur différentes facettes du rhum et mérite d'être découvert.",
                'category' => 1, // Guides
            ],
            [
                'title' => 'Le rhum agricole vs le rhum industriel',
                'excerpt' => 'Comprendre les différences fondamentales entre ces deux types de production.',
                'content' => "Le rhum agricole et le rhum industriel diffèrent principalement par leur matière première.\n\nLe rhum agricole est produit à partir de pur jus de canne à sucre fraîchement pressé. Il est principalement fabriqué dans les Antilles françaises et bénéficie de l'AOC Martinique. Ses arômes sont plus végétaux et complexes.\n\nLe rhum industriel (ou rhum traditionnel) est fabriqué à partir de mélasse, un sous-produit de l'industrie sucrière. Il représente 90% de la production mondiale et offre des saveurs plus douces et sucrées.\n\nChacun a ses partisans, et le choix dépend souvent des préférences personnelles et de l'utilisation prévue.",
                'category' => 1, // Guides
            ],
            [
                'title' => 'Nouvelle distillerie inaugurée en Martinique',
                'excerpt' => 'Une nouvelle distillerie vient d\'ouvrir ses portes, promettant des rhums d\'exception.',
                'content' => "La Martinique accueille une nouvelle distillerie qui promet de révolutionner le monde du rhum agricole.\n\nAvec des méthodes de production innovantes tout en respectant les traditions séculaires, cette distillerie utilise des cannes à sucre cultivées de manière biologique et des techniques de fermentation uniques.\n\nLes premiers lots devraient être disponibles d'ici quelques mois, et les amateurs de rhum attendent déjà avec impatience de découvrir ces nouvelles créations.",
                'category' => 0, // Actualités
            ],
        ];

        foreach ($posts as $key => $postData) {
            $post = new BlogPost();
            $post->setTitle($postData['title']);
            $post->setSlug($this->slugger->slug($postData['title'])->lower());
            $post->setExcerpt($postData['excerpt']);
            $post->setContent($postData['content']);
            $post->setCategory($categoryEntities[$postData['category']]);
            $post->setAuthor($admin);
            $post->setIsPublished(true);
            $post->setPublishedAt(new \DateTimeImmutable('-' . ($key * 3) . ' days'));
            
            $manager->persist($post);
        }

        $manager->flush();
        
        echo "✅ Fixtures du blog chargées avec succès !\n";
        echo "   - " . count($categories) . " catégories créées\n";
        echo "   - " . count($posts) . " articles créés\n";
    }
}