<?php

namespace App\Controller;

use App\Entity\BlogComment;
use App\Entity\BlogLike;
use App\Entity\BlogPost;
use App\Entity\User;
use App\Form\BlogCommentType;
use App\Repository\BlogCategoryRepository;
use App\Repository\BlogLikeRepository;
use App\Repository\BlogPostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/blog')]
class BlogController extends AbstractController
{
    public function __construct(
        private BlogPostRepository $postRepository,
        private BlogCategoryRepository $categoryRepository,
        private BlogLikeRepository $likeRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    private function getAuthenticatedUser(): ?User
    {
        $user = $this->getUser();
        return $user instanceof User ? $user : null;
    }

    /**
     * Liste des articles avec pagination et filtres
     */
    #[Route('', name: 'app_blog_index')]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $categoryId = $request->query->getInt('category');
        $search = $request->query->get('search');

        $paginator = $this->postRepository->findPublishedPaginated(
            $page,
            9,
            $categoryId ?: null,
            $search
        );

        $totalItems = count($paginator);
        $totalPages = ceil($totalItems / 9);

        $categories = $this->categoryRepository->findAllWithPostCount();
        $recentPosts = $this->postRepository->findRecentPublished(5);
        $popularPosts = $this->postRepository->findPopular(5);

        return $this->render('blog/index.html.twig', [
            'posts' => $paginator,
            'categories' => $categories,
            'recentPosts' => $recentPosts,
            'popularPosts' => $popularPosts,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'selectedCategory' => $categoryId,
            'search' => $search,
        ]);
    }

    /**
     * Afficher un article
     */
    #[Route('/{slug}', name: 'app_blog_show')]
    public function show(string $slug, Request $request): Response
    {
        $post = $this->postRepository->findOneBy([
            'slug' => $slug,
            'isPublished' => true
        ]);

        if (!$post) {
            throw $this->createNotFoundException('Article introuvable');
        }

        $user = $this->getAuthenticatedUser();

        // Formulaire de commentaire
        $comment = new BlogComment();
        $form = $this->createForm(BlogCommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$user) {
                $this->addFlash('error', 'Vous devez être connecté pour commenter.');
                return $this->redirectToRoute('app_login');
            }

            $comment->setPost($post);
            $comment->setAuthor($user);

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $this->addFlash('success', '✅ Votre commentaire a été ajouté avec succès.');

            return $this->redirectToRoute('app_blog_show', ['slug' => $slug]);
        }

        $relatedPosts = $this->postRepository->findBy(
            [
                'category' => $post->getCategory(),
                'isPublished' => true
            ],
            ['publishedAt' => 'DESC'],
            4
        );

        // Retirer l'article actuel des articles similaires
        $relatedPosts = array_filter($relatedPosts, fn($p) => $p->getId() !== $post->getId());
        $relatedPosts = array_slice($relatedPosts, 0, 3);

        return $this->render('blog/show.html.twig', [
            'post' => $post,
            'relatedPosts' => $relatedPosts,
            'commentForm' => $form,
        ]);
    }

    /**
     * Liker/Unliker un article
     */
    #[Route('/{id}/like', name: 'app_blog_like', methods: ['POST'])]
    public function like(int $id): Response
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour liker un article.');
            return $this->redirectToRoute('app_login');
        }

        $post = $this->postRepository->find($id);

        if (!$post || !$post->isPublished()) {
            throw $this->createNotFoundException('Article introuvable');
        }

        $existingLike = $this->likeRepository->findOneByUserAndPost($user, $post);

        if ($existingLike) {
            // Unlike
            $this->entityManager->remove($existingLike);
            $message = '💔 Vous n\'aimez plus cet article.';
        } else {
            // Like
            $like = new BlogLike();
            $like->setUser($user);
            $like->setPost($post);
            $this->entityManager->persist($like);
            $message = '❤️ Vous aimez cet article !';
        }

        $this->entityManager->flush();

        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_blog_show', ['slug' => $post->getSlug()]);
    }

    /**
     * Supprimer un commentaire (auteur seulement)
     */
    #[Route('/comment/{id}/delete', name: 'app_blog_comment_delete', methods: ['POST'])]
    public function deleteComment(int $id): Response
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $comment = $this->entityManager->getRepository(BlogComment::class)->find($id);

        if (!$comment) {
            throw $this->createNotFoundException('Commentaire introuvable');
        }

        if ($comment->getAuthor() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres commentaires.');
        }

        $postSlug = $comment->getPost()->getSlug();

        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        $this->addFlash('success', '✅ Commentaire supprimé.');

        return $this->redirectToRoute('app_blog_show', ['slug' => $postSlug]);
    }
}