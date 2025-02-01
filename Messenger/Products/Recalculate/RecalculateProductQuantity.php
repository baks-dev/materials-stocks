<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Materials\Stocks\Messenger\Products\Recalculate;

use BaksDev\Core\Cache\AppCacheInterface;
use BaksDev\Materials\Stocks\Repository\MaterialStocksTotal\MaterialStocksTotalInterface;
use BaksDev\Products\Product\Repository\ProductQuantity\ProductModificationQuantityInterface;
use BaksDev\Products\Product\Repository\ProductQuantity\ProductOfferQuantityInterface;
use BaksDev\Products\Product\Repository\ProductQuantity\ProductQuantityInterface;
use BaksDev\Products\Product\Repository\ProductQuantity\ProductVariationQuantityInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final readonly class RecalculateProductQuantity
{
    public function __construct(
        #[Target('materialsProductLogger')] private LoggerInterface $logger,
        private ProductModificationQuantityInterface $modificationQuantity,
        private ProductVariationQuantityInterface $variationQuantity,
        private ProductOfferQuantityInterface $offerQuantity,
        private ProductQuantityInterface $materialQuantity,
        private MaterialStocksTotalInterface $materialStocksTotal,
        private EntityManagerInterface $entityManager,
        private AppCacheInterface $cache,
    ) {}

    /**
     * Делает перерасчет указанной продукции и присваивает в карточку
     */
    public function __invoke(RecalculateProductMessage $material): void
    {
        $MaterialUpdateQuantity = null;

        // Количественный учет модификации множественного варианта торгового предложения
        if(null === $MaterialUpdateQuantity && $material->getModification())
        {

            $this->entityManager->clear();

            $MaterialUpdateQuantity = $this->modificationQuantity->getMaterialModificationQuantity(
                $material->getMaterial(),
                $material->getOffer(),
                $material->getVariation(),
                $material->getModification()
            );
        }

        // Количественный учет множественного варианта торгового предложения
        if(null === $MaterialUpdateQuantity && $material->getVariation())
        {
            $this->entityManager->clear();

            $MaterialUpdateQuantity = $this->variationQuantity->getMaterialVariationQuantity(
                $material->getMaterial(),
                $material->getOffer(),
                $material->getVariation()
            );
        }

        // Количественный учет торгового предложения
        if(null === $MaterialUpdateQuantity && $material->getOffer())
        {
            $this->entityManager->clear();

            $MaterialUpdateQuantity = $this->offerQuantity->getMaterialOfferQuantity(
                $material->getMaterial(),
                $material->getOffer()
            );
        }

        // Количественный учет продукта
        if(null === $MaterialUpdateQuantity)
        {
            $this->entityManager->clear();

            $MaterialUpdateQuantity = $this->materialQuantity->getMaterialQuantity(
                $material->getMaterial()
            );
        }

        if($MaterialUpdateQuantity)
        {
            // Метод возвращает общее количество продукции на всех складах (без учета резерва)
            $MaterialStocksTotal = $this->materialStocksTotal
                ->material($material->getMaterial())
                ->offer($material->getOffer())
                ->variation($material->getVariation())
                ->modification($material->getModification())
                ->get();

            $MaterialUpdateQuantity->setQuantity($MaterialStocksTotal);
            $this->entityManager->flush();

            $this->logger->info(
                'Складской учет: Обновили общее количество продукции в карточке',
                [
                    self::class.':'.__LINE__,
                    'total' => $MaterialStocksTotal,
                    'material' => (string) $material->getMaterial(),
                    'offer' => (string) $material->getOffer(),
                    'variation' => (string) $material->getVariation(),
                    'modification' => (string) $material->getModification(),
                ]
            );
        }

        /* Чистим кеш модуля продукции */
        $cache = $this->cache->init('materials-material');
        $cache->clear();

    }
}
