export const initializeLanding = () => {
    const landing = document.querySelector('[data-landing]');

    if (! landing || landing.dataset.landingInitialized) {
        return;
    }

    landing.dataset.landingInitialized = 'true';

    /* -------------------------------------------------------------
     * 1. Mobile Menu Toggle
     * ----------------------------------------------------------- */
    const menuButton = landing.querySelector('[data-landing-menu-toggle]');
    const mobileMenu = landing.querySelector('#landing-mobile-menu');

    if (menuButton && mobileMenu) {
        menuButton.hidden = false;

        const closeMenu = () => {
            mobileMenu.hidden = true;
            menuButton.setAttribute('aria-expanded', 'false');
            menuButton.setAttribute('aria-label', 'Buka navigasi');
        };

        menuButton.addEventListener('click', () => {
            const isExpanded = menuButton.getAttribute('aria-expanded') === 'true';
            mobileMenu.hidden = isExpanded;
            menuButton.setAttribute('aria-expanded', String(! isExpanded));
            menuButton.setAttribute('aria-label', isExpanded ? 'Buka navigasi' : 'Tutup navigasi');
        });

        mobileMenu.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', closeMenu);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && ! mobileMenu.hidden) {
                closeMenu();
                menuButton.focus();
            }
        });

        document.addEventListener('click', (event) => {
            if (! mobileMenu.hidden && ! mobileMenu.contains(event.target) && ! menuButton.contains(event.target)) {
                closeMenu();
            }
        });

        window.matchMedia('(min-width: 768px)').addEventListener('change', (event) => {
            if (event.matches) {
                closeMenu();
            }
        });
    }

    /* -------------------------------------------------------------
     * 2. Preview Workspace Tabs (Overview / Team / AI Insight)
     * ----------------------------------------------------------- */
    const tablist = landing.querySelector('[data-preview-tabs]');
    const tabs = [...landing.querySelectorAll('[data-preview-tab]')];
    const panels = [...landing.querySelectorAll('[data-preview-panel]')];

    if (tablist && tabs.length) {
        tablist.hidden = false;

        panels.forEach((panel) => {
            panel.setAttribute('role', 'tabpanel');
            panel.setAttribute('aria-labelledby', `preview-tab-${panel.dataset.previewPanel}`);
            panel.setAttribute('tabindex', '0');
        });

        const selectTab = (selectedTab) => {
            tabs.forEach((tab) => {
                const isSelected = tab === selectedTab;
                tab.setAttribute('aria-selected', String(isSelected));
                tab.tabIndex = isSelected ? 0 : -1;
            });

            panels.forEach((panel) => {
                panel.hidden = panel.dataset.previewPanel !== selectedTab.dataset.previewTab;
            });
        };

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => selectTab(tab));

            tab.addEventListener('keydown', (event) => {
                let nextIndex;

                if (event.key === 'ArrowRight') {
                    nextIndex = (index + 1) % tabs.length;
                } else if (event.key === 'ArrowLeft') {
                    nextIndex = (index - 1 + tabs.length) % tabs.length;
                } else if (event.key === 'Home') {
                    nextIndex = 0;
                } else if (event.key === 'End') {
                    nextIndex = tabs.length - 1;
                } else {
                    return;
                }

                event.preventDefault();
                selectTab(tabs[nextIndex]);
                tabs[nextIndex].focus();
            });
        });
    }

    /* -------------------------------------------------------------
     * 3. Interactive Hero SVG Sales Chart (Tooltips & Day Dots)
     * ----------------------------------------------------------- */
    const chartContainer = landing.querySelector('[data-landing-chart-container]');
    if (chartContainer) {
        const dots = [...chartContainer.querySelectorAll('[data-chart-dot]')];
        const dayBtns = [...chartContainer.querySelectorAll('[data-day-btn]')];
        const tooltip = chartContainer.querySelector('[data-chart-tooltip]');
        const tooltipText = chartContainer.querySelector('[data-chart-tooltip-text]');

        const activatePoint = (index) => {
            const dot = dots[index];
            if (! dot) {
                return;
            }

            dots.forEach((d) => d.classList.remove('is-active'));
            dot.classList.add('is-active');

            dayBtns.forEach((b) => b.classList.remove('is-active'));
            if (dayBtns[index]) {
                dayBtns[index].classList.add('is-active');
            }

            const cx = Number(dot.dataset.cx);
            const cy = Number(dot.dataset.cy);
            const day = dot.dataset.day.slice(0, 3);
            const val = dot.dataset.val;

            if (tooltip && tooltipText) {
                // Keep tooltip comfortably within SVG bounds (viewBox 0 0 420 145)
                const tooltipWidth = 112;
                let targetX = cx - (tooltipWidth / 2);
                if (targetX < 4) {
                    targetX = 4;
                }
                if (targetX + tooltipWidth > 416) {
                    targetX = 416 - tooltipWidth;
                }

                let targetY = cy - 32;
                if (targetY < 0) {
                    targetY = cy + 12;
                }

                tooltip.setAttribute('transform', `translate(${targetX}, ${targetY})`);
                tooltipText.textContent = `${day}: ${val}`;
            }
        };

        dots.forEach((dot, idx) => {
            dot.addEventListener('mouseenter', () => activatePoint(idx));
            dot.addEventListener('click', () => activatePoint(idx));
            dot.addEventListener('focus', () => activatePoint(idx));
        });

        dayBtns.forEach((btn, idx) => {
            btn.addEventListener('mouseenter', () => activatePoint(idx));
            btn.addEventListener('click', () => activatePoint(idx));
        });
    }

    /* -------------------------------------------------------------
     * 4. Interactive Task Checklist Simulation (Tab Aktivitas Tim)
     * ----------------------------------------------------------- */
    const interactiveTaskList = landing.querySelector('[data-interactive-task-list]');
    if (interactiveTaskList) {
        const taskItems = [...interactiveTaskList.querySelectorAll('[data-task-item]')];
        const tabCountBadge = landing.querySelector('[data-tab-task-count]');
        const teamDoneCount = landing.querySelector('[data-team-done-count]');
        const teamPercentLabel = landing.querySelector('[data-team-percent-label]');
        const overviewTaskLabel = landing.querySelector('[data-overview-task-label]');
        const overviewProgressBar = landing.querySelector('[data-overview-progress-bar]');
        const floatTaskLabel = landing.querySelector('[data-hero-float-task-label]');
        const resetBtn = landing.querySelector('[data-reset-tasks]');

        const updateTaskProgress = () => {
            // Baseline 7 tasks done from other members, plus status of 3 displayed demo tasks
            const baseDone = 7;
            const completedCount = taskItems.reduce((acc, item) => {
                return acc + (item.classList.contains('is-done') ? 1 : 0);
            }, baseDone);

            const total = 10;
            const percent = Math.round((completedCount / total) * 100);

            if (tabCountBadge) {
                tabCountBadge.textContent = `${completedCount}/${total}`;
            }
            if (teamDoneCount) {
                teamDoneCount.innerHTML = `${completedCount} <em>dari ${total}</em>`;
            }
            if (teamPercentLabel) {
                teamPercentLabel.textContent = `${percent}% selesai`;
            }
            if (overviewTaskLabel) {
                overviewTaskLabel.textContent = `${completedCount} dari ${total} selesai`;
            }
            if (overviewProgressBar) {
                overviewProgressBar.style.width = `${percent}%`;
            }
            if (floatTaskLabel) {
                floatTaskLabel.textContent = `${completedCount} dari ${total} Selesai`;
            }
        };

        taskItems.forEach((task) => {
            const toggle = () => {
                const isDone = task.classList.contains('is-done');
                task.classList.toggle('is-done', ! isDone);
                task.classList.toggle('is-pending', isDone);

                const badge = task.querySelector('[data-task-status-badge]');
                if (badge) {
                    badge.textContent = isDone ? 'Proses' : 'Selesai';
                    badge.classList.toggle('is-pending', isDone);
                }

                updateTaskProgress();
            };

            task.addEventListener('click', (e) => {
                e.preventDefault();
                toggle();
            });

            task.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggle();
                }
            });
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                taskItems.forEach((task, i) => {
                    const shouldBeDone = i < 2; // items 0 and 1 start as done, item 2 starts as pending
                    task.classList.toggle('is-done', shouldBeDone);
                    task.classList.toggle('is-pending', ! shouldBeDone);

                    const badge = task.querySelector('[data-task-status-badge]');
                    if (badge) {
                        badge.textContent = shouldBeDone ? 'Selesai' : 'Proses';
                        badge.classList.toggle('is-pending', ! shouldBeDone);
                    }
                });
                updateTaskProgress();
            });
        }
    }

    /* -------------------------------------------------------------
     * 5. Interactive AI Insight Prompt Switcher (Tab AI Insight)
     * ----------------------------------------------------------- */
    const aiDialogue = landing.querySelector('[data-ai-dialogue]');
    const aiChips = [...landing.querySelectorAll('[data-ai-chip]')];

    if (aiDialogue && aiChips.length) {
        const questionBubble = aiDialogue.querySelector('[data-ai-question-bubble]');
        const answerText = aiDialogue.querySelector('[data-ai-text]');
        const sourceTag = aiDialogue.querySelector('[data-ai-source]');

        const promptData = {
            trend: {
                question: 'Apa yang perlu saya perhatikan minggu ini?',
                answer: '<p>Dari data contoh Kedai Senja, penjualan paling tinggi terjadi pada akhir pekan (Sabtu & Minggu mencapai 42% omzet mingguan).</p><p class="mt-2">Pertimbangkan menyiapkan tim sebelum jam ramai dan periksa kembali tugas yang belum selesai agar pelayanan tetap optimal.</p>',
                source: 'Berdasarkan contoh penjualan 7 hari & laporan tugas',
            },
            products: {
                question: 'Produk apa yang menyumbang omzet tertinggi?',
                answer: '<p><strong>Kopi Susu Gula Aren</strong> memimpin dengan 142 cup terjual (+24% dibanding pekan lalu), disusul <strong>Croissant Cokelat</strong> sebanyak 89 porsi.</p><p class="mt-2">Kedua produk ini sering dipesan bersamaan pada pukul 08.00–11.00 WIB. Pertimbangkan promo bundling pagi untuk menaikkan nilai rata-rata pesanan.</p>',
                source: 'Berdasarkan data item penjualan Shopee, TikTok & kasir',
            },
            team: {
                question: 'Bagaimana ritme dan kedisiplinan tim hari ini?',
                answer: '<p>Tingkat ketepatan waktu tugas tim mencapai <strong>88%</strong>. Tugas buka toko dan pengecekan stok kasir selesai tepat waktu sebelum jam operasional dimulai.</p><p class="mt-2">Hanya tersisa penataan display oleh Sari yang sedang berjalan. Beban kerja karyawan hari ini terdistribusi secara seimbang.</p>',
                source: 'Berdasarkan catatan tugas harian & log penyelesaian tim',
            },
        };

        aiChips.forEach((chip) => {
            chip.addEventListener('click', () => {
                const key = chip.dataset.aiChip;
                const data = promptData[key];
                if (! data) {
                    return;
                }

                aiChips.forEach((c) => c.classList.remove('is-active'));
                chip.classList.add('is-active');

                // Smooth micro-transition
                aiDialogue.style.opacity = '0.3';
                aiDialogue.style.transition = 'opacity 180ms ease';

                setTimeout(() => {
                    if (questionBubble) {
                        questionBubble.textContent = data.question;
                    }
                    if (answerText) {
                        answerText.innerHTML = data.answer;
                    }
                    if (sourceTag) {
                        sourceTag.innerHTML = `<i data-lucide="file-chart-column" aria-hidden="true" class="size-3"></i> ${data.source}`;
                    }
                    aiDialogue.style.opacity = '1';
                }, 180);
            });
        });
    }

    /* -------------------------------------------------------------
     * 6. Interactive Audience Business Model Explorer
     * ----------------------------------------------------------- */
    const audienceExplorer = landing.querySelector('[data-audience-explorer]');
    if (audienceExplorer) {
        const audienceBtns = [...audienceExplorer.querySelectorAll('[data-audience-btn]')];
        const audienceTitle = audienceExplorer.querySelector('[data-audience-title]');
        const audienceText = audienceExplorer.querySelector('[data-audience-text]');
        const audienceDetailBox = audienceExplorer.querySelector('[data-audience-detail]');

        const audienceData = {
            retail: {
                title: 'Toko & Retail',
                text: 'Pantau kas harian toko, shift karyawan kasir, dan variasi produk yang paling laris tanpa perlu repot rekap nota manual di penghujung hari.',
            },
            culinary: {
                title: 'Kuliner & F&B',
                text: 'Kelola pesanan pelanggan meja maupun takeaway, pastikan kesiapan bahan baku setiap pagi, dan pantau tugas staf dapur tetap terarah.',
            },
            online: {
                title: 'Usaha Online',
                text: 'Tarik laporan pesanan Shopee & TikTok Seller tanpa rumus rumit. Pantau pembatalan, retur, dan produk terlaris dalam satu pandangan.',
            },
            team: {
                title: 'Tim Usaha',
                text: 'Bagikan tugas harian dengan tenggat waktu yang jelas. Karyawan memiliki ruang kerja terpisah sehingga fokus menyelesaikan tanggung jawabnya.',
            },
        };

        audienceBtns.forEach((btn) => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.audienceBtn;
                const info = audienceData[key];
                if (! info) {
                    return;
                }

                audienceBtns.forEach((b) => {
                    b.classList.remove('is-active');
                    b.setAttribute('aria-selected', 'false');
                });
                btn.classList.add('is-active');
                btn.setAttribute('aria-selected', 'true');

                if (audienceDetailBox) {
                    audienceDetailBox.style.opacity = '0.4';
                    audienceDetailBox.style.transform = 'translateY(4px)';
                    audienceDetailBox.style.transition = 'all 160ms ease';

                    setTimeout(() => {
                        if (audienceTitle) {
                            audienceTitle.textContent = info.title;
                        }
                        if (audienceText) {
                            audienceText.textContent = info.text;
                        }
                        audienceDetailBox.style.opacity = '1';
                        audienceDetailBox.style.transform = 'translateY(0)';
                    }, 160);
                }
            });
        });
    }

    /* -------------------------------------------------------------
     * 7. Interactive Feature Cards: Bar Chart, Tasks, AI Chip
     * ----------------------------------------------------------- */
    const featureChart = landing.querySelector('[data-feature-chart-container]');
    if (featureChart) {
        const barCols = [...featureChart.querySelectorAll('[data-bar-col]')];
        const salesDisplay = featureChart.querySelector('[data-feature-sales-display]');
        const salesChip = featureChart.querySelector('[data-feature-sales-chip]');
        const dayHint = featureChart.querySelector('[data-bar-day-hint]');

        barCols.forEach((col) => {
            const handleHover = () => {
                barCols.forEach((c) => c.classList.remove('is-active'));
                col.classList.add('is-active');

                if (salesDisplay && col.dataset.barVal) {
                    salesDisplay.textContent = col.dataset.barVal;
                }
                if (salesChip && col.dataset.barChip) {
                    salesChip.innerHTML = `<i data-lucide="trending-up" class="size-3" aria-hidden="true"></i> ${col.dataset.barChip}`;
                }
                if (dayHint && col.dataset.barDay) {
                    dayHint.textContent = `${col.dataset.barDay} · Penjualan harian`;
                }
            };

            col.addEventListener('mouseenter', handleHover);
            col.addEventListener('click', handleHover);
            col.addEventListener('focus', handleHover);
        });
    }

    const featureTasks = landing.querySelector('[data-feature-tasks]');
    if (featureTasks) {
        featureTasks.querySelectorAll('[data-feature-task]').forEach((item) => {
            const toggle = () => {
                const isDone = item.classList.contains('is-done');
                item.classList.toggle('is-done', ! isDone);
                item.classList.toggle('is-pending', isDone);
                const small = item.querySelector('small');
                if (small) {
                    const name = small.textContent.split('·').pop().trim();
                    small.textContent = isDone ? `Dalam proses · ${name}` : `Selesai · ${name}`;
                }
            };

            item.addEventListener('click', toggle);
            item.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggle();
                }
            });
        });
    }

    const featureAiBox = landing.querySelector('[data-feature-ai-box]');
    if (featureAiBox) {
        const featureChips = [...featureAiBox.querySelectorAll('[data-feature-chip]')];
        const questionEl = featureAiBox.querySelector('[data-feature-ai-question]');
        const textEl = featureAiBox.querySelector('[data-feature-ai-text]');

        const answers = {
            week: {
                q: 'Bagaimana penjualan minggu ini?',
                t: 'Penjualan tertinggi ada di akhir pekan. Coba perhatikan kesiapan tim dan stok bahan baku di hari tersebut.',
            },
            product: {
                q: 'Apa produk paling laris?',
                t: 'Kopi Susu Gula Aren menduduki peringkat teratas (+24%). Rekomendasi: siapkan kemasan siap saji sebelum jam sibuk.',
            },
            action: {
                q: 'Apa saran prioritas untuk besok?',
                t: 'Pastikan rekap pesanan pagi hari diunggah tepat waktu agar tim dapur dapat menyesuaikan persiapan bahan lebih awal.',
            },
        };

        featureChips.forEach((btn) => {
            btn.addEventListener('click', () => {
                const key = btn.dataset.featureChip;
                const item = answers[key];
                if (! item) {
                    return;
                }

                featureChips.forEach((b) => b.classList.remove('is-active'));
                btn.classList.add('is-active');

                if (questionEl) {
                    questionEl.textContent = item.q;
                }
                if (textEl) {
                    textEl.textContent = item.t;
                }
            });
        });
    }

    /* -------------------------------------------------------------
     * 8. Scroll Reveal Animations (IntersectionObserver)
     * ----------------------------------------------------------- */
    if ('IntersectionObserver' in window && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.remove('landing-reveal-ready');
                    entry.target.classList.add('landing-reveal-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08 });

        landing.querySelectorAll('[data-reveal]').forEach((element) => {
            if (element.getBoundingClientRect().top >= window.innerHeight) {
                element.classList.add('landing-reveal-ready');
                observer.observe(element);
            }
        });
    }
};
