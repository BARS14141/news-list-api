<?php

namespace App\Controller;

use App\Entity\News;
use App\Rabbit\NewsProducer;
use App\Rabbit\Producer\Producer;
use App\Repository\NewsRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NewsController extends AbstractController
{
    /**
     * @Route("/", name="add", methods={"POST"})
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function add(Request $request, EntityManagerInterface $entityManager, ProducerInterface $addNewsProducer): Response
    {
        try{
            $data = $this->getRequestContent($request);
            if (!$data || !$data['header'] || !$data['text'] || !$data['author']){
                throw new \Exception();
            }
            $news = [];
            $news['header'] = $data['header'];
            $news['text'] = $data['text'];
            $news['author'] = $data['author'];
            if (isset($data['publish_date']) && ($datetime = new DateTime($data['publish_date']))) {
                $news['publish_date'] = $data['publish_date'];
            }
            $addNewsProducer->publish(json_encode($news));
            return $this->json(['success' => "News added successfully"]);
        }catch (\Exception $e){
            return $this->json(['errors' => "Data no valid"], 422);
        }
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param NewsRepository $repository
     * @param News|null $news
     * @param $id
     * @return Response
     * @Route("/{id}", name="update", methods={"PUT"})
     */
    public function update(Request $request, EntityManagerInterface $entityManager, News $news = null): Response
    {
        try{
            if (!$news){
                return $this->json(['errors' => "News not found"], 404);
            }
            $data = $this->getRequestContent($request);
            if (!$data){
                throw new \Exception();
            }
            if (isset($data['header']) && $data['header']) {
                $news->setHeader($data['header']);
            }
            if (isset($data['text']) && $data['text']) {
                $news->setText($data['text']);
            }
            if (isset($data['author']) && $data['author']) {
                $news->setAuthor($data['author']);
            }
            if (isset($data['publish_date']) && ($datetime = new DateTime($data['publish_date']))) {
                $news->setPublishDate($datetime);
            }
            $entityManager->flush();

            return $this->json(['errors' => "News updated successfully"]);

        }catch (\Exception $e){
            return $this->json(['errors' => "Data no valid"], 422);
        }
    }

    /**
     * @Route("/{id}", name="delete", methods={"DELETE"})
     * @param News|null $news
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function delete(?News $news, EntityManagerInterface $entityManager): Response
    {
        if (!$news){
            return $this->json(['errors' => "News not found"], 404);
        }
        $entityManager->remove($news);
        $entityManager->flush();
        return $this->json(['errors' => "News deleted successfully"]);
    }

    /**
     * @Route("/search/", name="search", methods={"GET"})
     * @param Request $request
     * @param NewsRepository $repository
     * @return Response
     * @throws \Exception
     */
    public function search(Request $request, NewsRepository $repository): Response
    {
        $filter['id'] = $request->query->get('id') ?: [];
        $filter['minDate'] = $request->query->get('publish_date_min') ?
            new DateTime($request->query->get('publish_date_min')) ?: null : null;
        $filter['maxDate'] = $request->query->get('publish_date_max') ?
            new DateTime($request->query->get('publish_date_max')) ?: null : null;
        $filter['offset'] = $request->query->get('offset') ?: 0;
        $filter['limit'] = $request->query->get('limit') ?: 10;
        $entities = call_user_func_array([$repository, 'search'], $filter);
        return $this->json($entities);
    }

    /**
     * @Route("/count/in_days/", name="count_indays", methods={"GET"})
     * @param Request $request
     * @param NewsRepository $repository
     * @return Response
     * @throws \Exception
     */
    public function getCountInDays(Request $request, NewsRepository $repository): Response
    {
        $filter['minDate'] = $request->query->get('publish_date_min') ?
            new DateTime($request->query->get('publish_date_min')) ?: null : null;
        $filter['maxDate'] = $request->query->get('publish_date_max') ?
            new DateTime($request->query->get('publish_date_max')) ?: null : null;
        $entities = call_user_func_array([$repository, 'getCountByDays'], $filter);
        return $this->json($entities);
    }

    /**
     * @param News|null $news
     * @return Response
     * @Route("/{id}", name="getById", methods={"GET"})
     */
    public function getById(News $news = null): Response
    {
        if (!$news){
            return $this->json(['errors' => "News not found"], 404);
        }
        return $this->json($news);
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function getRequestContent(Request $request): array
    {
        return (array) json_decode($request->getContent(), true);
    }

}
