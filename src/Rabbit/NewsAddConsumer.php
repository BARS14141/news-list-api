<?php

namespace App\Rabbit;

use App\Entity\News;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class NewsAddConsumer implements ConsumerInterface
{

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function execute(AMQPMessage $msg)
    {
        $news = new News();
        $data = json_decode($msg->getBody(), true);
        $news->setHeader($data['header']);
        $news->setText($data['text']);
        $news->setAuthor($data['author']);
        if (isset($data['publish_date']) && ($datetime = new DateTime($data['publish_date']))) {
            $news->setPublishDate($datetime);
        }
        $this->entityManager->persist($news);
        $this->entityManager->flush();
        echo 'success';
    }

}