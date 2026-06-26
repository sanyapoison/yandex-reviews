import { chromium } from 'playwright';
import io from 'socket.io-client';

const socket = io("http://127.0.0.1:6001");
const socketId = process.argv[3];

(async () => {
    const inputUrl = process.argv[2];
    let result = { organization: {}, reviews: [] };
    let browser;

    try {
        socket.emit("parser.progress", { socketId, step: "Запуск парсера..." });

        const match = inputUrl.match(/org\/(?:[a-zA-Z0-9_-]+\/)?(\d+)/);
        if (!match) throw new Error("Некорректный URL организации");
        const orgId = match[1];

        socket.emit("parser.progress", { socketId, step: `Парсим организацию ${orgId}` });

        const baseUrl = `https://yandex.md/maps/org/${orgId}/`;
        const reviewsUrl = `${baseUrl}reviews/`;

        browser = await chromium.launch({ headless: true });
        const page = await browser.newPage();

        await page.goto(reviewsUrl, { waitUntil: 'domcontentloaded' });
        socket.emit("parser.progress", { socketId, step: "Страница загружена" });

        const safeText = async (selector) => {
            try {
                const el = await page.$(selector);
                return el ? (await el.textContent()).trim() : null;
            } catch {
                return null;
            }
        };

        result.organization.name = await safeText('.orgpage-header-view__header');
        const ratingText = await page.$$eval(
            '.business-summary-rating-badge-view__rating .business-summary-rating-badge-view__rating-text',
            nodes => nodes.map(n => n.textContent.trim()).join('')
        );
        result.organization.rating = ratingText ? parseFloat(ratingText.replace(',', '.')) : 0;

        result.organization.grades_count = parseInt((await safeText('.business-rating-amount-view._summary'))?.replace(/\D+/g, '') || 0);
        result.organization.reviews_count = parseInt((await safeText('.business-reviews-card-view._wide .business-reviews-card-view__title'))?.replace(/\D+/g, '') || 0);

        socket.emit("parser.progress", { socketId, step: "Получение отзывов организации: " + result.organization.reviews_count });

        await page.evaluate(async () => {
            const container = document.querySelector("div.sidebar-view__panel > div.scroll._width_wide > div");
            if (container) {
                for (let i = 0; i < 20; i++) {
                    container.scrollBy(0, container.scrollHeight);
                    await new Promise(r => setTimeout(r, 1000));
                }
            }
        });

        const reviewEls = await page.$$('.business-reviews-card-view__reviews-container .business-reviews-card-view__review');
        let reviewLastIndex = 0;

        for (const review of reviewEls) {
            const author = await review.$eval('.business-review-view__author-name', el => el.textContent.trim());
            const rating = await review.$eval('.business-rating-badge-view__stars._spacing_normal', el =>
                el.getAttribute('aria-label')?.match(/\d+/)?.[0]
            );
            const date = await review.$eval('.business-review-view__date meta[itemprop="datePublished"]', el =>
                el.getAttribute('content')
            );

            let text = await review.$eval('.business-review-view__body', el => el.textContent.trim());
            const spoilerBtn = await review.$('.spoiler-view__button');
            if (spoilerBtn) {
                try {
                    await spoilerBtn.click();
                    await review.waitForSelector('.spoiler-view__text-container', { timeout: 3000 });
                    text = await review.$eval('.spoiler-view__text-container', el => el.textContent.trim());
                } catch (err) {
                    result.errorRev = err.message;
                }
            }

            if (text) text = text.replace(/\n/g, '\n');

            reviewLastIndex = (await review.getAttribute('aria-posinset')).match(/\d+/)?.[0];

            // прогресс каждые 100 отзывов
            if (reviewLastIndex % 100 === 0) {
                socket.emit("parser.progress", { socketId, step: `Обработано ${reviewLastIndex} отзывов` });
            }

            result.reviews.push({ index: reviewLastIndex, author, rating, date, text });
        }

        if (result.organization.reviews_count == reviewLastIndex) {
            socket.emit("parser.success", { socketId, message: `Организация: ${result.organization.name} / отзывов: ${result.organization.reviews_count}` });
        } else {
            socket.emit("parser.error", { socketId, message: `Error parse reviews / reviews: ${result.organization.reviews_count} | ${reviewLastIndex}` });
        }

    } catch (err) {
        result.error = err.message;
        socket.emit("parser.error", { socketId, message: err.message });
    } finally {
        if (browser) await browser.close();
        socket.emit("parser.progress", { socketId, step: "Парсер завершил работу" });
        socket.disconnect();
    }

    console.log(JSON.stringify(result, null, 2));
})();
