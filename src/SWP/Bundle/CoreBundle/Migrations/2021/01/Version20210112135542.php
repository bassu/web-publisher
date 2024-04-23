<?php

declare(strict_types=1);

namespace SWP\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use SWP\Bundle\ContentBundle\Model\ArticleExtraEmbedField;
use SWP\Bundle\ContentBundle\Model\ArticleExtraTextField;
use SWP\Bundle\CoreBundle\Model\Article;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210112135542 extends AbstractMigration implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            'postgresql' !== $this->connection->getDatabasePlatform()->getName(),
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql('CREATE SEQUENCE IF NOT EXISTS swp_article_extra_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS swp_article_extra (id INT NOT NULL, article_id INT DEFAULT NULL, field_name VARCHAR(255) NOT NULL, discr VARCHAR(255) NOT NULL, value VARCHAR(255) DEFAULT NULL, embed VARCHAR(255) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))'
        );
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_9E61B3177294869C ON swp_article_extra (article_id)');
        $this->addSql(
            'ALTER TABLE swp_article_extra DROP CONSTRAINT IF EXISTS FK_9E61B3177294869C'
        );
        $this->addSql(
            'ALTER TABLE swp_article_extra ADD CONSTRAINT FK_9E61B3177294869C FOREIGN KEY (article_id) REFERENCES swp_article (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE'
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            'postgresql' !== $this->connection->getDatabasePlatform()->getName(),
            'Migration can only be executed safely on \'postgresql\'.'
        );

        $this->addSql('DROP SEQUENCE swp_article_extra_id_seq CASCADE');
        $this->addSql('DROP TABLE swp_article_extra');
    }

    public function postUp(Schema $schema): void
    {
        $entityManager = $this->container->get('doctrine.orm.default_entity_manager');
        $entityManager->getConnection()->getConfiguration()->setSQLLogger(null);

        $batchSize = 500;
        $numberOfRecordsPerPage = 2000;

        $totalArticles = $entityManager
            ->createQuery('SELECT count(a) FROM SWP\Bundle\CoreBundle\Model\Article a')
            ->getSingleScalarResult();

        $totalArticlesProcessed = 0;
        $isProcessing = true;

        while ($isProcessing) {
            $sql = "SELECT id, extra FROM swp_article LIMIT $numberOfRecordsPerPage OFFSET $totalArticlesProcessed";
            $query = $entityManager->getConnection()->prepare($sql);
            $query->execute();
            $results = $query->fetchAll();

            foreach ($results as $result) {
                $legacyExtra = $this->unserializeExtraField($result['extra']);
                if (!is_array($legacyExtra)) {
                    ++$totalArticlesProcessed;
                    continue;
                }

                $article = $entityManager->find(
                    Article::class,
                    $result['id']
                );
            try {
                 foreach ($legacyExtra as $key => $extraItem) {
                    if (is_array($extraItem)) {
                        $extra = ArticleExtraEmbedField::newFromValue($key, $extraItem);
                    } else {
                        $extra = ArticleExtraTextField::newFromValue($key, (string) $extraItem);
                    }
                    $extra->setArticle($article);
                    $entityManager->persist($extra);
                }
            } catch (\Throwable $e) {
                echo ">>>> E: " . $e->getMessage() . "\n";
            }

                ++$totalArticlesProcessed;
                if (0 == ($totalArticlesProcessed % $batchSize)) {
                    $entityManager->flush();
                    $entityManager->clear();
                }
            }

            // flush remaining entities in queue and break loop
            if ($totalArticlesProcessed === $totalArticles) {
                $entityManager->flush();
                $entityManager->clear();
                break;
            }
        }
    }

    private function unserializeExtraField(string $data)
    {
            $unserializedData = @unserialize($data);
    if ($unserializedData !== false) {
        return $unserializedData;
    }

    $callback = function ($matches) {
        $matches[2] = trim(preg_replace('/\s\s+/', ' ', $matches[2]));
        return 's:' . mb_strlen($matches[2]) . ':"' . $matches[2] . '";';
    };

    // Check if $data is not null
    if ($data !== null) {
        $data = preg_replace_callback('!s:(\d+):"(.*?)";!s', $callback, $data);
        return @unserialize($data);
    }

    return null; // Return null if $data is null
    }
}
