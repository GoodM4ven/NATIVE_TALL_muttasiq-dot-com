@assets
    <style>
        @font-face {
            font-family: 'MadinaQuran';
            src: url('/vendor/arabicable/madina.woff2') format('woff2');
            font-display: swap;
        }

        .quran-reader {
            --quran-panel-bg: color-mix(in srgb, hsl(38.18 64.71% 98.87%) 80%, transparent);
            --quran-panel-border: color-mix(in srgb, var(--warning-50) 38%, transparent);
            --quran-panel-shadow: 0 22px 40px color-mix(in srgb, var(--gray-900) 18%, transparent);
            --quran-panel-text: var(--primary-950);
            --quran-ink: color-mix(in srgb, var(--primary-950) 94%, var(--gray-900));
            --quran-subtle: color-mix(in srgb, var(--primary-700) 68%, var(--gray-500));
            --quran-chip-bg: color-mix(in srgb, var(--background-dark) 66%, transparent);
            --quran-chip-border: color-mix(in srgb, var(--primary-500) 35%, transparent);
            --quran-chip-hover: color-mix(in srgb, var(--primary-500) 16%, transparent);
            --quran-active-bg: color-mix(in srgb, var(--success-300) 30%, transparent);
            --quran-active-text: color-mix(in srgb, var(--success-500) 78%, var(--primary-900));
            --quran-page-surface: color-mix(in srgb, var(--background) 86%, transparent);
            --quran-page-border: color-mix(in srgb, var(--warning-300) 58%, transparent);
            --quran-page-scale: 1;
            --quran-min-page-scale: 0.1;
            --quran-max-page-scale: 1.85;
            --quran-type-scale: 1;
            --quran-leading-scale: 1;
            --quran-gap-scale: 1;
            --quran-page-type-scale: 1;
            --quran-page-leading-multiplier: 1;
            --quran-page-gap-multiplier: 1;
            --quran-page-surah-header-scale: 1;
            --quran-fit-height-ratio: 0.95;
            --quran-fit-target-width-ratio: 0.8;
            --quran-fit-area-pad-x: 0rem;
            --quran-fit-area-pad-y: 0rem;
            --quran-min-page-leading-multiplier: 0.9;
            --quran-min-page-gap-multiplier: 0.4;
            --quran-min-page-surah-header-scale: 0.9;
            --quran-min-basmallah-bottom-gap-scale: -0.32;
            --quran-line-gap: 1.65rem;
            --quran-basmallah-bottom-gap-scale: -0.18;
            --quran-surah-section-gap: calc(var(--quran-line-gap) * var(--quran-gap-scale) * var(--quran-page-gap-multiplier, 1) * 0.56);
            --quran-basmallah-top-gap: calc(var(--quran-line-gap) * var(--quran-gap-scale) * var(--quran-page-gap-multiplier, 1) * 0.12);
            --quran-surah-header-basmallah-overlap: calc(var(--quran-line-gap) * var(--quran-gap-scale) * var(--quran-page-gap-multiplier, 1) * -0.44);
            --quran-surah-header-bottom-trim: calc(var(--quran-line-gap) * var(--quran-gap-scale) * var(--quran-page-gap-multiplier, 1) * -0.1);
            --quran-surah-header-no-basmallah-first-ayah-gap-scale: -0.1;
            --quran-font-size-rect: 2.08rem;
            --quran-font-size-center: 2.02rem;
            --quran-font-size-meta: 1.88rem;
            --quran-line-height-rect: 1.58;
            --quran-line-height-center: 1.7;
            --quran-line-height-meta: 1.66;
        }

        .quran-page-lines--opening {
            --quran-page-opening-type-multiplier: 1;
            --quran-page-opening-leading-multiplier: 1;
            --quran-page-opening-gap-multiplier: 1;
            --quran-page-y-offset: 0rem;
        }

        .quran-page-lines--dense {
            --quran-page-dense-leading-multiplier: 1;
            --quran-page-dense-gap-multiplier: 1;
            --quran-page-dense-type-multiplier: 1;
            --quran-page-dense-y-offset: 0rem;
            --quran-page-y-offset: 0rem;
        }

        .quran-page-lines--headered-long {
            --quran-page-headered-type-multiplier: 1;
            --quran-page-headered-leading-multiplier: 1;
            --quran-page-headered-gap-multiplier: 1;
            --quran-surah-header-no-basmallah-first-ayah-gap-scale: -0.1;
            --quran-page-y-offset: 0rem;
        }

        .quran-page-lines--segmented {
            --quran-page-segmented-type-multiplier: 1;
            --quran-page-segmented-leading-multiplier: 1;
            --quran-page-segmented-gap-multiplier: 1;
            --quran-page-y-offset: 0rem;
        }

        .quran-page-lines {
            transform: translateY(var(--quran-page-y-offset, 0rem));
        }

        /* baseV1 */
        @media (max-width: 639px) {
            .quran-reader {
                --quran-min-page-scale: 0.2;
                --quran-page-scale: 0.98;
                --quran-max-page-scale: 1.38;
                --quran-fit-area-pad-x: 0.28rem;
                --quran-fit-area-pad-y: 0.24rem;
                --quran-fit-height-ratio: 0.93;
                --quran-fit-target-width-ratio: 0.86;
                --quran-min-page-gap-multiplier: 0.82;
                --quran-line-gap: 1.48rem;
                --quran-gap-scale: 0.92;
                --quran-page-gap-multiplier: 0.42;
                --quran-min-page-leading-multiplier: 0.9;
                --quran-leading-scale: 0.97;
                --quran-page-leading-multiplier: 1.1;
                --quran-min-page-surah-header-scale: 0.88;
                --quran-page-surah-header-scale: 1.01;
                --quran-min-basmallah-bottom-gap-scale: -0.34;
                --quran-basmallah-bottom-gap-scale: -0.28;
                --quran-type-scale: 1.04;
                --quran-page-type-scale: 1.16;
            }

            .quran-page-lines--opening {
                --quran-page-opening-type-multiplier: 1.08;
                --quran-page-opening-leading-multiplier: 1.02;
                --quran-page-opening-gap-multiplier: 0.9;
                --quran-page-scale: 0.47;
                --quran-page-type-scale: 1.3;
                --quran-page-leading-multiplier: 1.08;
                --quran-page-gap-multiplier: 1.28;
                --quran-page-surah-header-scale: 1.24;
                --quran-basmallah-bottom-gap-scale: -0.235;
                --quran-page-y-offset: 0rem;
            }

            .quran-page-lines--dense {
                --quran-page-dense-leading-multiplier: 1;
                --quran-page-dense-gap-multiplier: 1;
                --quran-page-dense-type-multiplier: 1;
                --quran-page-dense-y-offset: 0rem;
                --quran-page-y-offset: -0.5rem;
                --quran-page-scale: 0.355;
                --quran-page-type-scale: 1.35;
                --quran-page-leading-multiplier: 1.1;
                --quran-page-gap-multiplier: 0.96;
                --quran-page-surah-header-scale: 1.01;
                --quran-basmallah-bottom-gap-scale: -0.28;
            }

            .quran-page-lines--headered-long {
                --quran-page-headered-type-multiplier: 1;
                --quran-page-headered-leading-multiplier: 1;
                --quran-page-headered-gap-multiplier: 1;
                --quran-surah-header-no-basmallah-first-ayah-gap-scale: -0.1;
                --quran-page-scale: 0.285;
                --quran-page-type-scale: 1.66;
                --quran-page-leading-multiplier: 0.8;
                --quran-page-gap-multiplier: 1.04;
                --quran-page-surah-header-scale: 0.73;
                --quran-basmallah-bottom-gap-scale: -0.28;
                --quran-page-y-offset: -0.5rem;
            }

            .quran-page-lines--segmented {
                --quran-page-segmented-type-multiplier: 1;
                --quran-page-segmented-leading-multiplier: 1;
                --quran-page-segmented-gap-multiplier: 1;
                --quran-page-scale: 0.412;
                --quran-page-type-scale: 1.25;
                --quran-page-leading-multiplier: 1.2;
                --quran-page-gap-multiplier: 0.72;
                --quran-page-surah-header-scale: 0.81;
                --quran-basmallah-bottom-gap-scale: -0.28;
                --quran-page-y-offset: -0.5rem;
            }
        }

        /* BaseV2 */
        @media (max-width: 639px) and (min-height: 705px) {
            .quran-reader-panel {
                height: min(92svh, 40rem) !important;
            }

            .quran-page-lines--opening {
                --quran-page-opening-type-multiplier: 1.08;
                --quran-page-opening-leading-multiplier: 1.02;
                --quran-page-opening-gap-multiplier: 0.9;
                --quran-page-scale: 0.47;
                --quran-page-type-scale: 1.3;
                --quran-page-leading-multiplier: 1.08;
                --quran-page-gap-multiplier: 1.28;
                --quran-page-surah-header-scale: 1.24;
                --quran-basmallah-bottom-gap-scale: -0.235;
                --quran-page-y-offset: 0rem;
            }

            .quran-page-lines--dense {
                --quran-page-dense-leading-multiplier: 1;
                --quran-page-dense-gap-multiplier: 1;
                --quran-page-dense-type-multiplier: 1;
                --quran-page-dense-y-offset: 0rem;
                --quran-page-y-offset: 0rem;
                --quran-page-scale: 0.38;
                --quran-page-type-scale: 1.35;
                --quran-page-leading-multiplier: 1.1;
                --quran-page-gap-multiplier: 1.06;
                --quran-page-surah-header-scale: 1.01;
                --quran-basmallah-bottom-gap-scale: -0.28;
            }

            .quran-page-lines--headered-long {
                --quran-page-headered-type-multiplier: 1;
                --quran-page-headered-leading-multiplier: 1;
                --quran-page-headered-gap-multiplier: 1;
                --quran-surah-header-no-basmallah-first-ayah-gap-scale: -0.1;
                --quran-page-scale: 0.3;
                --quran-page-type-scale: 1.7;
                --quran-page-leading-multiplier: 0.8;
                --quran-page-gap-multiplier: 1.43;
                --quran-page-surah-header-scale: 0.73;
                --quran-basmallah-bottom-gap-scale: -0.28;
                --quran-page-y-offset: 0rem;
            }

            .quran-page-lines--segmented {
                --quran-page-segmented-type-multiplier: 1;
                --quran-page-segmented-leading-multiplier: 1;
                --quran-page-segmented-gap-multiplier: 1;
                --quran-page-scale: 0.43;
                --quran-page-type-scale: 1.27;
                --quran-page-leading-multiplier: 1.2;
                --quran-page-gap-multiplier: 0.92;
                --quran-page-surah-header-scale: 0.81;
                --quran-basmallah-bottom-gap-scale: -0.28;
                --quran-page-y-offset: 0rem;
            }
        }

        /* BaseV3 */
        @media (max-width: 639px) and (min-height: 821px) {
            .quran-reader-panel {
                width: min(91vw, 25rem) !important;
                height: min(92svh, 47rem) !important;
            }

            .quran-page-lines--opening {
                --quran-page-opening-type-multiplier: 1.08;
                --quran-page-opening-leading-multiplier: 1.02;
                --quran-page-opening-gap-multiplier: 0.9;
                --quran-page-scale: 0.57;
                --quran-page-type-scale: 1.3;
                --quran-page-leading-multiplier: 1.08;
                --quran-page-gap-multiplier: 1.48;
                --quran-page-surah-header-scale: 1.24;
                --quran-basmallah-bottom-gap-scale: -0.235;
                --quran-page-y-offset: 0rem;
            }

            .quran-page-lines--dense {
                --quran-page-dense-leading-multiplier: 1;
                --quran-page-dense-gap-multiplier: 1;
                --quran-page-dense-type-multiplier: 1;
                --quran-page-dense-y-offset: 0rem;
                --quran-page-y-offset: 0rem;
                --quran-page-scale: 0.49;
                --quran-page-type-scale: 1.3;
                --quran-page-leading-multiplier: 1.1;
                --quran-page-gap-multiplier: 1.7;
                --quran-page-surah-header-scale: 1.01;
                --quran-basmallah-bottom-gap-scale: -0.28;
            }

            .quran-page-lines--headered-long {
                --quran-page-headered-type-multiplier: 1;
                --quran-page-headered-leading-multiplier: 1;
                --quran-page-headered-gap-multiplier: 1;
                --quran-surah-header-no-basmallah-first-ayah-gap-scale: -0.1;
                --quran-page-scale: 0.38;
                --quran-page-type-scale: 1.66;
                --quran-page-leading-multiplier: 0.8;
                --quran-page-gap-multiplier: 1.97;
                --quran-page-surah-header-scale: 0.73;
                --quran-basmallah-bottom-gap-scale: -0.28;
                --quran-page-y-offset: 0rem;
            }

            .quran-page-lines--segmented {
                --quran-page-segmented-type-multiplier: 1;
                --quran-page-segmented-leading-multiplier: 1;
                --quran-page-segmented-gap-multiplier: 1;
                --quran-page-scale: 0.512;
                --quran-page-type-scale: 1.25;
                --quran-page-leading-multiplier: 1.2;
                --quran-page-gap-multiplier: 1.37;
                --quran-page-surah-header-scale: 0.81;
                --quran-basmallah-bottom-gap-scale: -0.28;
                --quran-page-y-offset: 0rem;
            }
        }

        /* sm */
        @media (min-width: 640px) and (max-width: 767px) {
            .quran-reader {
                --quran-min-page-scale: 0.2;
                --quran-page-scale: 0.95;
                --quran-max-page-scale: 1.34;
                --quran-fit-area-pad-x: 0.34rem;
                --quran-fit-area-pad-y: 0.26rem;
                --quran-fit-height-ratio: 0.94;
                --quran-fit-target-width-ratio: 0.84;
                --quran-min-page-gap-multiplier: 0.84;
                --quran-line-gap: 1.51rem;
                --quran-gap-scale: 0.9;
                --quran-page-gap-multiplier: 0.58;
                --quran-min-page-leading-multiplier: 0.9;
                --quran-leading-scale: 1;
                --quran-page-leading-multiplier: 1.14;
                --quran-min-page-surah-header-scale: 0.88;
                --quran-page-surah-header-scale: 1.012;
                --quran-min-basmallah-bottom-gap-scale: -0.34;
                --quran-basmallah-bottom-gap-scale: -0.285;
                --quran-type-scale: 0.97;
                --quran-page-type-scale: 1.01;
                --quran-font-size-rect: 1.94rem;
                --quran-font-size-center: 1.9rem;
                --quran-font-size-meta: 1.8rem;
                --quran-line-height-rect: 1.62;
                --quran-line-height-center: 1.72;
                --quran-line-height-meta: 1.68;
            }

            .quran-page-lines--opening {
                --quran-page-opening-type-multiplier: 1.06;
                --quran-page-opening-leading-multiplier: 1.015;
                --quran-page-opening-gap-multiplier: 0.92;
                --quran-page-scale: 0.78;
                --quran-page-type-scale: 1.08;
                --quran-page-leading-multiplier: 1.18;
                --quran-page-gap-multiplier: 1.3;
                --quran-page-surah-header-scale: 1.13;
                --quran-basmallah-bottom-gap-scale: -0.24;
                --quran-page-y-offset: 0rem;
            }

            .quran-page-lines--dense {
                --quran-page-dense-leading-multiplier: 1.34;
                --quran-page-dense-gap-multiplier: 1.68;
                --quran-page-dense-type-multiplier: 0.92;
                --quran-page-dense-y-offset: 0.5rem;
                --quran-page-y-offset: 0.5rem;
                --quran-page-scale: 0.88;
                --quran-page-type-scale: 0.92;
                --quran-page-leading-multiplier: 1.14;
                --quran-page-gap-multiplier: 0.6;
                --quran-page-surah-header-scale: 1.012;
                --quran-basmallah-bottom-gap-scale: -0.285;
            }

            .quran-page-lines--headered-long {
                --quran-page-headered-type-multiplier: 0.88;
                --quran-page-headered-leading-multiplier: 1.32;
                --quran-page-headered-gap-multiplier: 1.56;
                --quran-surah-header-no-basmallah-first-ayah-gap-scale: -0.1;
                --quran-page-scale: 0.96;
                --quran-page-type-scale: 0.855;
                --quran-page-leading-multiplier: 0.18;
                --quran-page-gap-multiplier: 0.54;
                --quran-page-surah-header-scale: 0.65;
                --quran-basmallah-bottom-gap-scale: -0.575;
                --quran-page-y-offset: -0.5rem;
            }

            .quran-page-lines--segmented {
                --quran-page-segmented-type-multiplier: 0.82;
                --quran-page-segmented-leading-multiplier: 1.32;
                --quran-page-segmented-gap-multiplier: 1.52;
                --quran-page-scale: 0.84;
                --quran-page-type-scale: 1.02;
                --quran-page-leading-multiplier: 0.54;
                --quran-page-gap-multiplier: 0.65;
                --quran-page-surah-header-scale: 0.812;
                --quran-basmallah-bottom-gap-scale: -0.285;
                --quran-page-y-offset: -0.5rem;
            }
        }

        /* md */
        @media (min-width: 768px) and (max-width: 1023px) {
            .quran-reader {
                --quran-min-page-scale: 0.2;
                --quran-page-scale: 0.98;
                --quran-max-page-scale: 1.36;
                --quran-fit-area-pad-x: 0.45rem;
                --quran-fit-area-pad-y: 0.3rem;
                --quran-fit-height-ratio: 0.945;
                --quran-fit-target-width-ratio: 0.84;
                --quran-min-page-gap-multiplier: 0.85;
                --quran-line-gap: 1.49rem;
                --quran-gap-scale: 0.88;
                --quran-page-gap-multiplier: 0.56;
                --quran-min-page-leading-multiplier: 0.91;
                --quran-leading-scale: 1;
                --quran-page-leading-multiplier: 1.11;
                --quran-min-page-surah-header-scale: 0.88;
                --quran-page-surah-header-scale: 1.015;
                --quran-min-basmallah-bottom-gap-scale: -0.35;
                --quran-basmallah-bottom-gap-scale: -0.29;
                --quran-type-scale: 1;
                --quran-page-type-scale: 1.03;
                --quran-font-size-rect: 1.98rem;
                --quran-font-size-center: 1.94rem;
                --quran-font-size-meta: 1.84rem;
                --quran-line-height-rect: 1.6;
                --quran-line-height-center: 1.7;
                --quran-line-height-meta: 1.66;
            }

            .quran-page-lines--opening {
                --quran-page-opening-type-multiplier: 1.05;
                --quran-page-opening-leading-multiplier: 1.01;
                --quran-page-opening-gap-multiplier: 0.94;
                --quran-page-scale: 0.8;
                --quran-page-type-scale: 1.25;
                --quran-page-leading-multiplier: 1.06;
                --quran-page-gap-multiplier: 1.32;
                --quran-page-surah-header-scale: 1.13;
                --quran-basmallah-bottom-gap-scale: -0.242;
                --quran-page-y-offset: 0rem;
            }

            .quran-page-lines--dense {
                --quran-page-dense-leading-multiplier: 1.32;
                --quran-page-dense-gap-multiplier: 1.62;
                --quran-page-dense-type-multiplier: 0.94;
                --quran-page-dense-y-offset: -0.01rem;
                --quran-page-y-offset: -0.01rem;
                --quran-page-scale: 0.85;
                --quran-page-type-scale: 1.05;
                --quran-page-leading-multiplier: 1.01;
                --quran-page-gap-multiplier: 0.68;
                --quran-page-surah-header-scale: 0.98;
                --quran-basmallah-bottom-gap-scale: -0.39;
            }

            .quran-page-lines--headered-long {
                --quran-page-headered-type-multiplier: 0.9;
                --quran-page-headered-leading-multiplier: 1.28;
                --quran-page-headered-gap-multiplier: 1.5;
                --quran-surah-header-no-basmallah-first-ayah-gap-scale: -0.1;
                --quran-page-scale: 0.78;
                --quran-page-type-scale: 1.06;
                --quran-page-leading-multiplier: 0.31;
                --quran-page-gap-multiplier: 0.81;
                --quran-page-surah-header-scale: 0.68;
                --quran-basmallah-bottom-gap-scale: -0.39;
                --quran-page-y-offset: -0.5rem;
            }

            .quran-page-lines--segmented {
                --quran-page-segmented-type-multiplier: 0.84;
                --quran-page-segmented-leading-multiplier: 1.28;
                --quran-page-segmented-gap-multiplier: 1.48;
                --quran-page-scale: 0.78;
                --quran-page-type-scale: 1.24;
                --quran-page-leading-multiplier: 0.31;
                --quran-page-gap-multiplier: 0.89;
                --quran-page-surah-header-scale: 0.58;
                --quran-basmallah-bottom-gap-scale: -0.49;
                --quran-page-y-offset: -0.2rem;
            }
        }

        /* lg */
        @media (min-width: 1024px) and (max-width: 1279px) {
            .quran-reader {
                --quran-min-page-scale: 0.2;
                --quran-page-scale: 1;
                --quran-max-page-scale: 1.38;
                --quran-fit-area-pad-x: 0.7rem;
                --quran-fit-area-pad-y: 0.35rem;
                --quran-fit-height-ratio: 0.95;
                --quran-fit-target-width-ratio: 0.845;
                --quran-min-page-gap-multiplier: 0.86;
                --quran-line-gap: 1.69rem;
                --quran-gap-scale: 0.85;
                --quran-page-gap-multiplier: 0.58;
                --quran-min-page-leading-multiplier: 0.915;
                --quran-leading-scale: 1;
                --quran-page-leading-multiplier: 1.2;
                --quran-min-page-surah-header-scale: 0.88;
                --quran-page-surah-header-scale: 1.02;
                --quran-min-basmallah-bottom-gap-scale: -0.36;
                --quran-basmallah-bottom-gap-scale: -0.295;
                --quran-type-scale: 1.03;
                --quran-page-type-scale: 1.07;
                --quran-font-size-rect: 2.02rem;
                --quran-font-size-center: 1.98rem;
                --quran-font-size-meta: 1.88rem;
                --quran-line-height-rect: 1.68;
                --quran-line-height-center: 1.78;
                --quran-line-height-meta: 1.74;
            }

            .quran-page-lines--opening {
                --quran-page-opening-type-multiplier: 1.04;
                --quran-page-opening-leading-multiplier: 1.01;
                --quran-page-opening-gap-multiplier: 1.02;
                --quran-page-scale: 0.76;
                --quran-page-type-scale: 1.05;
                --quran-page-leading-multiplier: 0.8;
                --quran-page-gap-multiplier: 0.99;
                --quran-page-surah-header-scale: 1.03;
                --quran-basmallah-bottom-gap-scale: -0.24;
                --quran-page-y-offset: 0rem;
            }

            .quran-page-lines--dense {
                --quran-page-dense-leading-multiplier: 1.3;
                --quran-page-dense-gap-multiplier: 1.6;
                --quran-page-dense-type-multiplier: 0.92;
                --quran-page-dense-y-offset: 0.1rem;
                --quran-page-y-offset: -0.57rem;
                --quran-page-scale: 0.55;
                --quran-page-type-scale: 1.18;
                --quran-page-leading-multiplier: 1.2;
                --quran-page-gap-multiplier: 0.69;
                --quran-page-surah-header-scale: 1.02;
                --quran-basmallah-bottom-gap-scale: -0.295;
            }

            .quran-page-lines--headered-long {
                --quran-page-headered-type-multiplier: 0.92;
                --quran-page-headered-leading-multiplier: 1.24;
                --quran-page-headered-gap-multiplier: 1.44;
                --quran-surah-header-no-basmallah-first-ayah-gap-scale: -0.1;
                --quran-page-scale: 0.46;
                --quran-page-type-scale: 1.4;
                --quran-page-leading-multiplier: 0.6;
                --quran-page-gap-multiplier: 0.77;
                --quran-page-surah-header-scale: 0.7;
                --quran-basmallah-bottom-gap-scale: -0.5;
                --quran-page-y-offset: -0.5rem;
            }

            .quran-page-lines--segmented {
                --quran-page-segmented-type-multiplier: 0.86;
                --quran-page-segmented-leading-multiplier: 1.24;
                --quran-page-segmented-gap-multiplier: 1.42;
                --quran-page-scale: 0.62;
                --quran-page-type-scale: 1.16;
                --quran-page-leading-multiplier: 0.7;
                --quran-page-gap-multiplier: 0.65;
                --quran-page-surah-header-scale: 0.58;
                --quran-basmallah-bottom-gap-scale: -0.495;
                --quran-page-y-offset: -0.5rem;
            }
        }

        /* xl */
        @media (min-width: 1280px) and (max-width: 1535px) {
            .quran-reader {
                --quran-type-scale: 1;
                --quran-page-type-scale: 1;
                --quran-min-page-scale: 0.5;
                --quran-max-page-scale: 1.2;
                --quran-page-scale: 0.94;
                --quran-fit-area-pad-x: 0.7rem;
                --quran-fit-area-pad-y: 0.35rem;
                --quran-fit-target-width-ratio: 0.82;
                --quran-fit-height-ratio: 0.94;
                --quran-min-page-gap-multiplier: 0.5;
                --quran-line-gap: 1rem;
                --quran-gap-scale: 1;
                --quran-page-gap-multiplier: 0.5;
                --quran-min-page-leading-multiplier: 1;
                --quran-leading-scale: 1;
                --quran-page-leading-multiplier: 1;
                --quran-min-page-surah-header-scale: 0.5;
                --quran-page-surah-header-scale: 1;
                --quran-min-basmallah-bottom-gap-scale: -0.36;
                --quran-basmallah-bottom-gap-scale: -0.295;
                --quran-font-size-rect: 2rem;
                --quran-font-size-center: 2rem;
                --quran-font-size-meta: 1.85rem;
                --quran-line-height-rect: 1.5;
                --quran-line-height-center: 1.6;
                --quran-line-height-meta: 1.6;
            }

            .quran-page-lines--opening {
                --quran-page-opening-type-multiplier: 1.02;
                --quran-page-opening-leading-multiplier: 1.01;
                --quran-page-opening-gap-multiplier: 1.02;
                --quran-page-scale: 0.65;
                --quran-page-type-scale: 1.1;
                --quran-page-leading-multiplier: 1.1;
                --quran-page-gap-multiplier: 1.3;
                --quran-page-surah-header-scale: 1;
                --quran-basmallah-bottom-gap-scale: -0.295;
                --quran-page-y-offset: 0rem;
            }

            .quran-page-lines--dense {
                --quran-page-dense-leading-multiplier: 1.32;
                --quran-page-dense-gap-multiplier: 1.72;
                --quran-page-dense-type-multiplier: 0.97;
                --quran-page-dense-y-offset: 0.08rem;
                --quran-page-y-offset: -0.52rem;
                --quran-page-scale: 0.34;
                --quran-page-type-scale: 1.66;
                --quran-page-leading-multiplier: 1;
                --quran-page-gap-multiplier: 0.76;
                --quran-page-surah-header-scale: 1;
                --quran-basmallah-bottom-gap-scale: -0.295;
            }

            .quran-page-lines--headered-long {
                --quran-page-headered-type-multiplier: 0.98;
                --quran-page-headered-leading-multiplier: 1.24;
                --quran-page-headered-gap-multiplier: 1.54;
                --quran-surah-header-no-basmallah-first-ayah-gap-scale: -0.1;
                --quran-page-scale: 0.38;
                --quran-page-type-scale: 1.4;
                --quran-page-leading-multiplier: 0.6;
                --quran-page-gap-multiplier: 0.65;
                --quran-page-surah-header-scale: 0.7;
                --quran-basmallah-bottom-gap-scale: -0.695;
                --quran-page-y-offset: -0.5rem;
            }

            .quran-page-lines--segmented {
                --quran-page-segmented-type-multiplier: 0.965;
                --quran-page-segmented-leading-multiplier: 1.01;
                --quran-page-segmented-gap-multiplier: 1.08;
                --quran-page-scale: 0.54;
                --quran-page-type-scale: 1.065;
                --quran-page-leading-multiplier: 1;
                --quran-page-gap-multiplier: 0.84;
                --quran-page-surah-header-scale: 0.7;
                --quran-basmallah-bottom-gap-scale: -0.695;
                --quran-page-y-offset: -0.5rem;
            }
        }

        /* 2xl */
        @media (min-width: 1536px) and (max-width: 1919px) {
            .quran-reader {
                --quran-max-page-scale: 1.31;
                --quran-fit-area-pad-x: 1.5rem;
                --quran-fit-area-pad-y: 0.45rem;
                --quran-fit-target-width-ratio: 0.9;
                --quran-fit-height-ratio: 0.98;
                --quran-line-gap: 1.91rem;
                --quran-min-page-leading-multiplier: 0.85;
                --quran-min-page-gap-multiplier: 0.95;
                --quran-min-page-surah-header-scale: 0.86;
                --quran-basmallah-bottom-gap-scale: -0.3;
                --quran-min-basmallah-bottom-gap-scale: -0.36;
                --quran-page-scale: 1.43;
                --quran-min-page-scale: 0.1;
                --quran-type-scale: 1;
                --quran-leading-scale: 0.9;
                --quran-gap-scale: 1.23;
                --quran-page-type-scale: 1.2;
                --quran-page-leading-multiplier: 1;
                --quran-page-gap-multiplier: 0.5;
                --quran-page-surah-header-scale: 1;
                --quran-font-size-rect: 2.08rem;
                --quran-font-size-center: 2.02rem;
                --quran-font-size-meta: 1.88rem;
                --quran-line-height-rect: 1.58;
                --quran-line-height-center: 1.7;
                --quran-line-height-meta: 1.66;
            }

            .quran-page-lines--opening {
                --quran-page-segmented-type-multiplier: 1.02;
                --quran-page-segmented-leading-multiplier: 1.045;
                --quran-page-segmented-gap-multiplier: 1.12;
                --quran-page-scale: 0.5;
                --quran-page-type-scale: 1.2;
                --quran-page-leading-multiplier: 0.9;
                --quran-page-gap-multiplier: 0.56;
                --quran-page-surah-header-scale: 0.7;
                --quran-basmallah-bottom-gap-scale: -0.3;
                --quran-page-y-offset: -0.4rem;
            }

            .quran-page-lines--dense {
                --quran-page-dense-leading-multiplier: 1.07;
                --quran-page-dense-gap-multiplier: 1.14;
                --quran-page-dense-type-multiplier: 1;
                --quran-page-dense-y-offset: -0.02rem;
                --quran-page-y-offset: -0.32rem;
                --quran-page-scale: 0.46;
                --quran-page-type-scale: 1.25;
                --quran-page-leading-multiplier: 1;
                --quran-page-gap-multiplier: 0.56;
                --quran-page-surah-header-scale: 1;
                --quran-basmallah-bottom-gap-scale: -0.3;
            }

            .quran-page-lines--headered-long {
                --quran-page-headered-type-multiplier: 1.034;
                --quran-page-headered-leading-multiplier: 1.1;
                --quran-page-headered-gap-multiplier: 1.2;
                --quran-surah-header-no-basmallah-first-ayah-gap-scale: 0.3;
                --quran-page-scale: 0.36;
                --quran-page-type-scale: 1.47;
                --quran-page-leading-multiplier: 0.9;
                --quran-page-gap-multiplier: 0.61;
                --quran-page-surah-header-scale: 0.8;
                --quran-basmallah-bottom-gap-scale: -0.3;
                --quran-page-y-offset: -0.5rem;
            }

            .quran-page-lines--segmented {
                --quran-page-segmented-type-multiplier: 1.02;
                --quran-page-segmented-leading-multiplier: 1.045;
                --quran-page-segmented-gap-multiplier: 1.12;
                --quran-page-scale: 0.49;
                --quran-page-type-scale: 1.2;
                --quran-page-leading-multiplier: 0.9;
                --quran-page-gap-multiplier: 0.56;
                --quran-page-surah-header-scale: 0.7;
                --quran-basmallah-bottom-gap-scale: -0.3;
                --quran-page-y-offset: -0.2rem;
            }
        }

        /* 3xl */
        @media (min-width: 1920px) and (max-width: 2559px) {
            .quran-reader {
                --quran-max-page-scale: 1.3;
                --quran-fit-area-pad-x: 1.1rem;
                --quran-fit-area-pad-y: 0.3rem;
                --quran-fit-target-width-ratio: 0.85;
                --quran-fit-height-ratio: 0.92;
                --quran-line-gap: 1.75rem;
                --quran-min-page-leading-multiplier: 0.85;
                --quran-min-page-gap-multiplier: 0.9;
                --quran-min-page-surah-header-scale: 0.86;
                --quran-basmallah-bottom-gap-scale: -0.3;
                --quran-min-basmallah-bottom-gap-scale: -0.36;
                --quran-page-scale: 1;
                --quran-min-page-scale: 0.1;
                --quran-type-scale: 1;
                --quran-leading-scale: 1;
                --quran-gap-scale: 1;
                --quran-page-type-scale: 1;
                --quran-page-leading-multiplier: 1;
                --quran-page-gap-multiplier: 1;
                --quran-page-surah-header-scale: 1;
                --quran-font-size-rect: 2.08rem;
                --quran-font-size-center: 2.02rem;
                --quran-font-size-meta: 1.88rem;
                --quran-line-height-rect: 1.58;
                --quran-line-height-center: 1.7;
                --quran-line-height-meta: 1.66;
            }

            .quran-page-lines--opening {
                --quran-page-opening-type-multiplier: 1.03;
                --quran-page-opening-leading-multiplier: 1.03;
                --quran-page-opening-gap-multiplier: 1.06;
                --quran-page-scale: 0.8;
                --quran-page-type-scale: 1.1;
                --quran-page-leading-multiplier: 1;
                --quran-page-gap-multiplier: 0.9;
                --quran-page-surah-header-scale: 1;
                --quran-basmallah-bottom-gap-scale: -0.3;
                --quran-page-y-offset: 0rem;
            }

            .quran-page-lines--dense {
                --quran-page-dense-leading-multiplier: 1.06;
                --quran-page-dense-gap-multiplier: 1.12;
                --quran-page-dense-type-multiplier: 1.01;
                --quran-page-dense-y-offset: 0.26rem;
                --quran-page-y-offset: 0.26rem;
                --quran-page-scale: 0.655;
                --quran-page-type-scale: 1.07;
                --quran-page-leading-multiplier: 1;
                --quran-page-gap-multiplier: 0.9;
                --quran-page-surah-header-scale: 1;
                --quran-basmallah-bottom-gap-scale: -0.3;
            }

            .quran-page-lines--headered-long {
                --quran-page-headered-type-multiplier: 1.038;
                --quran-page-headered-leading-multiplier: 1.11;
                --quran-page-headered-gap-multiplier: 1.22;
                --quran-surah-header-no-basmallah-first-ayah-gap-scale: 0.3;
                --quran-page-scale: 0.595;
                --quran-page-type-scale: 1.1;
                --quran-page-leading-multiplier: 0.7;
                --quran-page-gap-multiplier: 0.88;
                --quran-page-surah-header-scale: 0.8;
                --quran-basmallah-bottom-gap-scale: -0.3;
                --quran-page-y-offset: -0.1rem;
            }

            .quran-page-lines--segmented {
                --quran-page-segmented-type-multiplier: 1.018;
                --quran-page-segmented-leading-multiplier: 1.04;
                --quran-page-segmented-gap-multiplier: 1.11;
                --quran-page-scale: 0.68;
                --quran-page-type-scale: 1.05;
                --quran-page-leading-multiplier: 0.9;
                --quran-page-gap-multiplier: 0.9;
                --quran-page-surah-header-scale: 0.7;
                --quran-basmallah-bottom-gap-scale: -0.4;
                --quran-page-y-offset: 0rem;
            }
        }

        /* 4xl */
        @media (min-width: 2560px) {
            .quran-reader {
                --quran-max-page-scale: 1.25;
                --quran-fit-area-pad-x: 1rem;
                --quran-fit-area-pad-y: 0.3rem;
                --quran-fit-target-width-ratio: 0.85;
                --quran-fit-height-ratio: 0.9;
                --quran-line-gap: 1.75rem;
                --quran-min-page-leading-multiplier: 0.8;
                --quran-min-page-gap-multiplier: 0.9;
                --quran-min-page-surah-header-scale: 0.86;
                --quran-min-basmallah-bottom-gap-scale: -0.36;
                --quran-page-scale: 0.69;
                --quran-page-type-scale: 0.95;
                --quran-page-leading-multiplier: 0.8;
                --quran-page-gap-multiplier: 1;
                --quran-page-surah-header-scale: 0.8;
                --quran-basmallah-bottom-gap-scale: -0.3;
            }

            .quran-page-lines--opening {
                --quran-page-opening-type-multiplier: 1.04;
                --quran-page-opening-leading-multiplier: 1.04;
                --quran-page-opening-gap-multiplier: 1.08;
                --quran-page-scale: 0.9;
                --quran-page-type-scale: 0.9;
                --quran-page-leading-multiplier: 0.7;
                --quran-page-gap-multiplier: 0.8;
                --quran-page-surah-header-scale: 1.1;
                --quran-basmallah-bottom-gap-scale: -0.3;
                --quran-page-y-offset: 0rem;
            }

            .quran-page-lines--dense {
                --quran-page-dense-leading-multiplier: 1.14;
                --quran-page-dense-gap-multiplier: 1.32;
                --quran-page-dense-type-multiplier: 1.022;
                --quran-page-dense-y-offset: -0.08rem;
                --quran-page-y-offset: 0.22rem;
                --quran-page-scale: 0.67;
                --quran-page-type-scale: 1.05;
                --quran-page-leading-multiplier: 1;
                --quran-page-gap-multiplier: 0.72;
                --quran-page-surah-header-scale: 1;
                --quran-basmallah-bottom-gap-scale: -0.3;
            }

            .quran-page-lines--headered-long {
                --quran-page-headered-type-multiplier: 1.05;
                --quran-page-headered-leading-multiplier: 1.03;
                --quran-page-headered-gap-multiplier: 1.25;
                --quran-surah-header-no-basmallah-first-ayah-gap-scale: -0.1;
                --quran-page-scale: 0.56;
                --quran-page-type-scale: 1.18;
                --quran-page-leading-multiplier: 0.6;
                --quran-page-gap-multiplier: 0.89;
                --quran-page-surah-header-scale: 0.8;
                --quran-basmallah-bottom-gap-scale: -0.2;
                --quran-page-y-offset: -0.7rem;
            }

            .quran-page-lines--segmented {
                --quran-page-segmented-type-multiplier: 1.024;
                --quran-page-segmented-leading-multiplier: 1.05;
                --quran-page-segmented-gap-multiplier: 1.13;
                --quran-page-scale: 0.76;
                --quran-page-type-scale: 0.95;
                --quran-page-leading-multiplier: 0.4;
                --quran-page-gap-multiplier: 0.8;
                --quran-page-surah-header-scale: 0.7;
                --quran-basmallah-bottom-gap-scale: -0.4;
                --quran-page-y-offset: -0.2rem;
            }
        }

        .dark .quran-reader {
            --quran-panel-bg: color-mix(in srgb, var(--primary-200) 24%, transparent);
            --quran-panel-border: color-mix(in srgb, var(--primary-300) 42%, transparent);
            --quran-panel-shadow: 0 24px 44px color-mix(in srgb, var(--gray-950) 62%, transparent);
            --quran-panel-text: var(--primary-50);
            --quran-ink: color-mix(in srgb, var(--primary-50) 94%, white);
            --quran-subtle: color-mix(in srgb, var(--primary-100) 72%, var(--gray-300));
            --quran-chip-bg: color-mix(in srgb, var(--gray-950) 44%, transparent);
            --quran-chip-border: color-mix(in srgb, var(--primary-200) 42%, transparent);
            --quran-chip-hover: color-mix(in srgb, var(--primary-300) 25%, transparent);
            --quran-active-bg: color-mix(in srgb, var(--success-300) 24%, transparent);
            --quran-active-text: color-mix(in srgb, var(--success-100) 90%, white);
            --quran-page-surface: color-mix(in srgb, var(--gray-950) 44%, transparent);
            --quran-page-border: color-mix(in srgb, var(--gray-700) 65%, transparent);
        }

        .quran-reader-panel {
            color: var(--quran-panel-text);
            background: var(--quran-panel-bg);
            border-color: var(--quran-panel-border);
            box-shadow: var(--quran-panel-shadow);
        }

        .quran-page-surface {
            /* background: var(--quran-page-surface); */
            border-color: var(--quran-page-border);
        }

        .font-quran {
            font-family: 'MadinaQuran', 'Amiri', 'Traditional Arabic', serif;
        }

        .quran-ayah-line-run {
            display: inline-flex;
            direction: rtl;
            align-items: baseline;
            gap: var(--quran-word-gap-extra, 0em);
            white-space: nowrap;
            max-width: none;
        }

        .quran-segment-cluster {
            display: inline-flex;
            align-items: baseline;
            gap: var(--quran-word-gap-extra, 0em);
            border-radius: 0.38em;
            padding-inline: 0.14em;
            margin-inline: -0.02em;
            transition:
                background-color 460ms cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 460ms cubic-bezier(0.22, 1, 0.36, 1),
                color 360ms cubic-bezier(0.22, 1, 0.36, 1);
            will-change: background-color, box-shadow;
        }

        .quran-segment-cluster.quran-segment-cluster-hovered {
            background-color: color-mix(in srgb, var(--gray-300) 18%, transparent);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--gray-400) 10%, transparent),
                0 2px 8px color-mix(in srgb, var(--gray-700) 8%, transparent);
        }

        .quran-segment-cluster.quran-segment-cluster-active,
        .quran-segment-cluster.quran-segment-cluster-search-highlighted {
            background: var(--quran-active-bg);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--success-300) 20%, transparent),
                0 2px 10px color-mix(in srgb, var(--success-400) 12%, transparent);
        }

        .quran-segment-cluster.quran-segment-cluster-search-highlighted {
            animation: quran-search-highlight-enter 300ms cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .quran-segment-cluster.quran-segment-cluster-copied {
            background: color-mix(in srgb, var(--warning-200) 60%, transparent);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--warning-500) 26%, transparent),
                0 2px 12px color-mix(in srgb, var(--warning-700) 18%, transparent);
            animation: quran-copy-highlight-enter 220ms cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .quran-word-button {
            display: inline-flex;
            align-items: baseline;
            white-space: nowrap;
            line-height: 1.02;
            border-radius: 0;
            padding-inline: 0;
            cursor: default;
            transition:
                background-color 440ms cubic-bezier(0.22, 1, 0.36, 1),
                color 360ms cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 440ms cubic-bezier(0.22, 1, 0.36, 1);
            will-change: background-color, color, box-shadow;
        }

        .quran-word-button.quran-segment-hovered {
            background-color: color-mix(in srgb, var(--gray-300) 18%, transparent);
            box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--gray-400) 8%, transparent);
            border-radius: 0.52em;
        }

        .quran-word-button.quran-segment-active {
            background: var(--quran-active-bg);
            color: var(--quran-active-text);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--success-300) 20%, transparent),
                0 2px 10px color-mix(in srgb, var(--success-400) 12%, transparent);
            border-radius: 0.52em;
        }

        .quran-word-button.quran-segment-copied {
            background: color-mix(in srgb, var(--warning-200) 64%, transparent);
            color: color-mix(in srgb, var(--warning-900) 70%, var(--quran-ink));
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--warning-500) 30%, transparent),
                0 2px 12px color-mix(in srgb, var(--warning-700) 16%, transparent);
            border-radius: 0.52em;
            animation: quran-copy-highlight-enter 220ms cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .dark .quran-segment-cluster.quran-segment-cluster-hovered {
            background-color: color-mix(in srgb, var(--gray-400) 12%, transparent);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--gray-400) 14%, transparent),
                0 2px 10px color-mix(in srgb, white 6%, transparent);
        }

        .dark .quran-word-button.quran-segment-hovered {
            background-color: color-mix(in srgb, var(--gray-400) 12%, transparent);
            box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--gray-400) 12%, transparent);
        }

        .dark .quran-segment-cluster.quran-segment-cluster-copied {
            background: color-mix(in srgb, var(--warning-300) 34%, transparent);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--warning-300) 22%, transparent),
                0 2px 10px color-mix(in srgb, var(--warning-700) 10%, transparent);
        }

        .dark .quran-word-button.quran-segment-copied {
            background: color-mix(in srgb, var(--warning-300) 36%, transparent);
            color: color-mix(in srgb, var(--warning-100) 60%, var(--quran-ink));
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--warning-300) 24%, transparent),
                0 2px 10px color-mix(in srgb, var(--warning-700) 10%, transparent);
        }

        @keyframes quran-copy-highlight-enter {
            from {
                opacity: 0;
                transform: translateY(0.08rem) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes quran-search-highlight-enter {
            from {
                opacity: 0.4;
                transform: translateY(0.06rem) scale(0.985);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .quran-ayah-marker {
            font-family: 'IBM Plex Sans Arabic', 'Manrope', ui-sans-serif, system-ui, sans-serif;
            line-height: 1;
        }

        .quran-surah-header-line {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 100%;
            padding: calc(0.42rem * var(--quran-page-scale)) 0;
            font-size: calc(var(--quran-font-size-meta) * 1.5 * var(--quran-type-scale) * var(--quran-page-type-scale) * var(--quran-page-surah-header-scale) * var(--quran-page-scale));
            line-height: 1;
            color: color-mix(in srgb, var(--primary-600) 86%, var(--quran-ink));
            background: transparent;
            box-shadow: none;
            letter-spacing: normal;
            text-wrap: nowrap;
        }

        .dark .quran-surah-header-line {
            color: color-mix(in srgb, var(--primary-100) 88%, var(--quran-ink));
        }

        .quran-surah-header-line--fatiha {
            margin-block-end: 1rem;
        }

        .quran-surah-header-line::before,
        .quran-surah-header-line::after {
            content: '';
            position: absolute;
            inset-inline: 0;
            height: 1px;
            background: linear-gradient(to right,
                    transparent 0%,
                    color-mix(in srgb, var(--quran-subtle) 22%, transparent) 14%,
                    color-mix(in srgb, var(--quran-subtle) 54%, transparent) 50%,
                    color-mix(in srgb, var(--quran-subtle) 22%, transparent) 86%,
                    transparent 100%);
        }

        .quran-surah-header-line::before {
            inset-block-start: 0;
        }

        .quran-surah-header-line::after {
            inset-block-end: 0;
        }

        .quran-surah-header-glyph {
            display: inline-block;
            position: relative;
            z-index: 1;
            padding-inline: 0.8rem;
            line-height: 1;
        }

        .quran-page-lines {
            transition: opacity 180ms ease;
            opacity: 0;
            user-select: none;
            -webkit-user-select: none;
            cursor: default;
            width: max-content;
            max-width: none;
            direction: rtl;
            display: flex;
            flex-direction: column;
            gap: calc(var(--quran-line-gap) * var(--quran-gap-scale) * var(--quran-page-gap-multiplier) * var(--quran-page-scale) * var(--quran-page-dense-gap-multiplier, 1) * var(--quran-page-segmented-gap-multiplier, 1) * var(--quran-page-headered-gap-multiplier, 1) * var(--quran-page-opening-gap-multiplier, 1));
        }

        .quran-page-lines * {
            user-select: none;
            -webkit-user-select: none;
            cursor: default;
        }

        .quran-calibration-overlay {
            color: color-mix(in srgb, var(--quran-panel-text) 88%, var(--gray-900));
            contain: paint;
            transform: translateZ(0);
            will-change: opacity;
            isolation: isolate;
            /* overflow: hidden; */
        }

        .quran-calibration-overlay::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background:
                linear-gradient(145deg,
                    color-mix(in srgb, var(--background) 30%, transparent) 0%,
                    color-mix(in srgb, var(--quran-panel-bg) 55%, transparent) 100%);
            border: 1px solid color-mix(in srgb, var(--quran-panel-border) 64%, transparent);
            box-shadow:
                inset 0 1px 0 color-mix(in srgb, var(--gray-50) 28%, transparent),
                0 12px 28px color-mix(in srgb, var(--gray-900) 18%, transparent);
            backdrop-filter: blur(11px) saturate(1.12);
            -webkit-backdrop-filter: blur(11px) saturate(1.12);
            z-index: 0;
            pointer-events: none;
        }

        .quran-calibration-overlay.quran-calibration-overlay--visible {
            opacity: 1;
            visibility: visible;
            transition:
                opacity 180ms ease-out,
                visibility 0ms linear 0ms;
        }

        .quran-calibration-overlay.quran-calibration-overlay--hidden {
            opacity: 0;
            visibility: hidden;
            transition:
                opacity 220ms ease-in,
                visibility 0ms linear 220ms;
        }

        .quran-calibration-loader {
            transform: translate3d(0, 0, 0);
            will-change: transform, opacity;
            backface-visibility: hidden;
            contain: layout style;
            overflow: visible;
            isolation: isolate;
            pointer-events: none;
        }

        .quran-calibration-hud {
            position: fixed;
            display: grid;
            top: 0 !important;
            left: 0 !important;
            inset: 0;
            justify-content: center;
            align-items: center;
            /* inset-inline-start: 0; */
            /* inset-block-start: 0; */
            /* transform: translate3d(-50%, -50%, 0); */
            z-index: 120;
            pointer-events: none;
            opacity: 0;
            visibility: hidden;
            will-change: transform, opacity;
            transition:
                opacity 220ms ease,
                visibility 0ms linear 220ms;
            /* contain: layout style; */
            /* overflow: visible; */
            isolation: isolate;
            backface-visibility: hidden;
        }

        .quran-calibration-hud.quran-calibration-hud--visible {
            opacity: 1;
            visibility: visible;
            transition:
                opacity 220ms ease,
                visibility 0ms linear 0ms;
        }

        /* Credits: https://uiball.com/ldrs/ (jelly triangle) */
        .quran-calibration-spinner {
            position: relative;
            display: grid;
            align-items: center;
            place-items: center;
            transform: translate3d(0, 0, 0);
            backface-visibility: hidden;
            contain: layout style;
            overflow: visible;
            pointer-events: none;
            filter: drop-shadow(0 0 0.35rem color-mix(in srgb, var(--primary-400) 34%, transparent));
        }

        .quran-calibration-jelly-svg {
            --uib-color: #0a6571;
            --uib-speed: 1.75s;
            display: block;
            inline-size: 100%;
            block-size: 100%;
            overflow: visible;
        }

        .quran-calibration-jelly-group {
            filter: url('#quran-calibration-jelly-ooze');
        }

        .quran-calibration-jelly-node {
            fill: var(--uib-color);
            transform-box: fill-box;
            transform-origin: center;
            will-change: transform;
            transition: fill 300ms ease;
        }

        .quran-calibration-jelly-dot-top {
            animation: quran-calibration-jelly-grow var(--uib-speed) ease infinite;
        }

        .quran-calibration-jelly-dot-right {
            animation: quran-calibration-jelly-grow var(--uib-speed) ease calc(var(--uib-speed) * -0.666) infinite;
        }

        .quran-calibration-jelly-dot-left {
            animation: quran-calibration-jelly-grow var(--uib-speed) ease calc(var(--uib-speed) * -0.333) infinite;
        }

        .quran-calibration-jelly-traveler {
            animation: quran-calibration-jelly-triangulate var(--uib-speed) ease infinite;
        }

        @keyframes quran-calibration-jelly-triangulate {

            0%,
            100% {
                transform: none;
            }

            33.333% {
                transform: translate(10px, 20px);
            }

            66.666% {
                transform: translate(-10px, 20px);
            }
        }

        @keyframes quran-calibration-jelly-grow {

            0%,
            85%,
            100% {
                transform: scale(1.5);
            }

            50%,
            60% {
                transform: scale(0);
            }
        }

        .quran-page-lines[data-fit-state='fading-out'] {
            opacity: 0;
            visibility: visible;
            transition: opacity 120ms ease-out;
        }

        .quran-page-lines[data-fit-state='fitting'] {
            opacity: 0;
            visibility: hidden;
            transition: none;
        }

        .quran-page-lines[data-fit-state='ready'] {
            opacity: 1;
            visibility: visible;
            transition: opacity 180ms ease-in;
        }

        .quran-page-lines[data-fit-state='ready'] [data-quran-line] {
            animation: quran-line-reveal 440ms ease both;
            animation-delay: calc(var(--quran-line-index, 0) * 18ms);
        }

        @keyframes quran-line-reveal {
            from {
                opacity: 0;
                transform: translateY(0.42rem);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .quran-ayah-line-fit {
            max-width: 100%;
        }

        .quran-ayah-line-run-rect {
            font-size: calc(var(--quran-font-size-rect) * var(--quran-type-scale) * var(--quran-page-type-scale) * var(--quran-page-scale) * var(--quran-page-dense-type-multiplier, 1) * var(--quran-page-segmented-type-multiplier, 1) * var(--quran-page-headered-type-multiplier, 1) * var(--quran-page-opening-type-multiplier, 1));
            line-height: calc(var(--quran-line-height-rect) * var(--quran-leading-scale) * var(--quran-page-leading-multiplier) * var(--quran-page-dense-leading-multiplier, 1) * var(--quran-page-segmented-leading-multiplier, 1) * var(--quran-page-headered-leading-multiplier, 1) * var(--quran-page-opening-leading-multiplier, 1));
        }

        .quran-ayah-line-run-centered {
            font-size: calc(var(--quran-font-size-center) * var(--quran-type-scale) * var(--quran-page-type-scale) * var(--quran-page-scale) * var(--quran-page-dense-type-multiplier, 1) * var(--quran-page-segmented-type-multiplier, 1) * var(--quran-page-headered-type-multiplier, 1) * var(--quran-page-opening-type-multiplier, 1));
            line-height: calc(var(--quran-line-height-center) * var(--quran-leading-scale) * var(--quran-page-leading-multiplier) * var(--quran-page-dense-leading-multiplier, 1) * var(--quran-page-segmented-leading-multiplier, 1) * var(--quran-page-headered-leading-multiplier, 1) * var(--quran-page-opening-leading-multiplier, 1));
        }

        .quran-meta-line {
            font-size: calc(var(--quran-font-size-meta) * var(--quran-type-scale) * var(--quran-page-type-scale) * var(--quran-page-scale) * var(--quran-page-dense-type-multiplier, 1) * var(--quran-page-segmented-type-multiplier, 1) * var(--quran-page-headered-type-multiplier, 1) * var(--quran-page-opening-type-multiplier, 1));
            line-height: calc(var(--quran-line-height-meta) * var(--quran-leading-scale) * var(--quran-page-leading-multiplier) * var(--quran-page-dense-leading-multiplier, 1) * var(--quran-page-segmented-leading-multiplier, 1) * var(--quran-page-headered-leading-multiplier, 1) * var(--quran-page-opening-leading-multiplier, 1));
        }

        .quran-basmallah-line {
            display: inline-flex;
            align-items: baseline;
            justify-content: center;
            gap: 0.22ch;
            white-space: nowrap;
            font-size: calc(var(--quran-font-size-center) * var(--quran-type-scale) * var(--quran-page-type-scale) * var(--quran-page-scale) * var(--quran-page-dense-type-multiplier, 1) * var(--quran-page-segmented-type-multiplier, 1) * var(--quran-page-headered-type-multiplier, 1) * var(--quran-page-opening-type-multiplier, 1));
            line-height: calc(var(--quran-line-height-center) * var(--quran-leading-scale) * var(--quran-page-leading-multiplier) * var(--quran-page-dense-leading-multiplier, 1) * var(--quran-page-segmented-leading-multiplier, 1) * var(--quran-page-headered-leading-multiplier, 1) * var(--quran-page-opening-leading-multiplier, 1));
        }

        .quran-basmallah-word {
            display: inline-block;
        }

        .quran-page-motion-next {
            animation: quran-page-slide-next 260ms ease-out;
        }

        .quran-page-motion-prev {
            animation: quran-page-slide-prev 260ms ease-out;
        }

        .quran-top-strip {
            position: relative;
            z-index: 40;
            display: flex;
            align-items: center;
            opacity: 0;
            transition: opacity 220ms ease;
        }

        .quran-top-strip.quran-top-strip--initial-loading {
            pointer-events: none;
        }

        .quran-top-strip.quran-top-strip--visible {
            opacity: 1;
        }

        .quran-top-actions {
            display: flex;
            flex: 1 1 auto;
            align-items: center;
            justify-content: flex-end;
            min-width: 0;
        }

        @media (min-width: 1280px) and (max-width: 1535px) {
            .quran-top-actions {
                gap: 0.42rem;
            }
        }

        .quran-top-actions.quran-top-actions--wird-active {
            gap: 0;
        }

        .quran-top-actions-secondary {
            opacity: 1;
            transform: translateX(0) scale(1);
            overflow: hidden;
            transition:
                opacity 260ms ease,
                transform 320ms cubic-bezier(0.2, 1, 0.36, 1),
                inline-size 320ms cubic-bezier(0.2, 1, 0.36, 1),
                max-width 320ms ease,
                margin 320ms ease;
        }

        .quran-top-actions.quran-top-actions--wird-active .quran-top-actions-secondary {
            inline-size: 0;
            max-width: 0;
            opacity: 0;
            margin: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .quran-top-actions.quran-top-actions--wird-active #quran-reader-history-toggle.quran-top-actions-secondary {
            transform: translateX(0.6rem) scale(0.84);
        }

        .quran-top-actions.quran-top-actions--wird-active #quran-reader-bookmark-toggle.quran-top-actions-secondary {
            transform: translateX(-0.6rem) scale(0.84);
        }

        /* Credits: https://dribbble.com/shots/26540115-Progress-Bar */
        .quran-wird-progress-button {
            --quran-wird-progress-percent: 0%;
            --quran-wird-progress-browse-percent: 0%;
            --quran-wird-frame-cut: 0.82rem;
            --quran-wird-frame-cut-inner: 0.7rem;
            position: relative;
            display: inline-flex;
            flex: 1 1 auto;
            align-items: center;
            justify-content: stretch;
            padding: 0.16rem;
            border-radius: 999px;
            border: 0;
            background: transparent;
            color: color-mix(in srgb, var(--gray-900) 82%, var(--quran-panel-text));
            overflow: visible;
            isolation: isolate;
            transition:
                box-shadow 260ms ease,
                min-width 320ms cubic-bezier(0.2, 1, 0.36, 1);
        }

        .quran-top-actions.quran-top-actions--wird-active .quran-wird-progress-button {
            min-width: 100%;
        }

        .quran-wird-progress-button::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 999px;
            clip-path: polygon(var(--quran-wird-frame-cut) 0,
                    calc(100% - var(--quran-wird-frame-cut)) 0,
                    100% 50%,
                    calc(100% - var(--quran-wird-frame-cut)) 100%,
                    var(--quran-wird-frame-cut) 100%,
                    0 50%);
            /* box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--success-500) 50%, transparent); */
            background: linear-gradient(180deg,
                    color-mix(in srgb, var(--gray-100) 88%, white 12%),
                    color-mix(in srgb, var(--gray-200) 66%, transparent));
            opacity: 1;
            pointer-events: none;
            z-index: 0;
        }

        .quran-wird-progress-button::after {
            content: '';
            position: absolute;
            inset: 0.16rem;
            border-radius: 999px;
            clip-path: polygon(var(--quran-wird-frame-cut-inner) 0,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 0,
                    100% 50%,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 100%,
                    var(--quran-wird-frame-cut-inner) 100%,
                    0 50%);
            background: repeating-linear-gradient(115deg,
                    color-mix(in srgb, var(--gray-300) 24%, transparent) 0 0.62rem,
                    color-mix(in srgb, var(--gray-100) 14%, transparent) 0.62rem 1.04rem);
            pointer-events: none;
            z-index: 3;
            opacity: 0.48;
        }

        .quran-wird-progress-button:hover {}

        .quran-wird-progress-button:active {}

        .quran-wird-progress-aura-water,
        .quran-wird-progress-aura-reflect {
            position: absolute;
            inset: 0.16rem;
            border-radius: 999px;
            clip-path: polygon(var(--quran-wird-frame-cut-inner) 0,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 0,
                    100% 50%,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 100%,
                    var(--quran-wird-frame-cut-inner) 100%,
                    0 50%);
            pointer-events: none;
            z-index: 1;
        }

        .quran-wird-progress-aura-water {
            background:
                radial-gradient(70% 84% at 18% 28%, rgb(56 189 248 / 44%) 0%, transparent 68%),
                radial-gradient(66% 88% at 82% 74%, rgb(192 132 252 / 40%) 0%, transparent 72%),
                radial-gradient(58% 64% at 54% 34%, rgb(251 191 36 / 28%) 0%, transparent 64%);
            mix-blend-mode: screen;
            opacity: 0.66;
        }

        .quran-wird-progress-aura-reflect {
            background:
                linear-gradient(115deg,
                    rgb(255 255 255 / 0%) 12%,
                    rgb(255 255 255 / 32%) 28%,
                    rgb(255 255 255 / 0%) 44%,
                    rgb(236 72 153 / 18%) 62%,
                    rgb(255 255 255 / 0%) 80%),
                repeating-linear-gradient(125deg,
                    rgb(255 255 255 / 0%) 0 0.9rem,
                    rgb(255 255 255 / 12%) 0.9rem 1.22rem,
                    rgb(255 255 255 / 0%) 1.22rem 1.68rem);
            background-size: 190% 180%, 170% 170%;
            background-position: 12% 48%, 18% 60%;
            mix-blend-mode: plus-lighter;
            opacity: 0.42;
            filter: saturate(1.2);
        }

        /* Credits: https://codepen.io/pugson/pen/eYNXvyN */
        .quran-wird-progress-aura-rainbow {
            position: absolute;
            -webkit-mask-image: radial-gradient(ellipse at center, black 60%, transparent 100%);
            mask-image: radial-gradient(ellipse at center, black 60%, transparent 100%);
            inset: -0.07rem;
            border-radius: 999px;
            clip-path: polygon(var(--quran-wird-frame-cut-inner) 0,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 0,
                    100% 50%,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 100%,
                    var(--quran-wird-frame-cut-inner) 100%,
                    0 50%);
            opacity: 0;
            pointer-events: none;
            z-index: -1;
        }

        .quran-wird-progress-aura-rainbow::before,
        .quran-wird-progress-aura-rainbow::after {
            position: absolute;
            content: '';
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(90deg,
                    var(--success-300),
                    var(--success-400),
                    var(--success-500),
                    var(--success-400),
                    var(--success-300));
            background-size: 200% 200%;
            animation: quran-wird-rainbow-glow 10s linear infinite;
        }

        .quran-wird-progress-aura-rainbow::before {
            filter: blur(12px);
            transform: scale(1.08);
            opacity: 0.8;
        }

        .quran-wird-progress-aura-rainbow::after {
            filter: blur(18px);
            transform: scale(1.14);
            opacity: 0.52;
        }

        .quran-wird-progress-button--active-aura .quran-wird-progress-aura-rainbow {
            opacity: 1;
        }

        .quran-wird-progress-button:hover .quran-wird-progress-aura-water,
        .quran-wird-progress-button:focus-visible .quran-wird-progress-aura-water {
            opacity: 0.82;
        }

        .quran-wird-progress-hover-shimmer {
            position: absolute;
            inset: 0.16rem;
            border-radius: 999px;
            clip-path: polygon(var(--quran-wird-frame-cut-inner) 0,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 0,
                    100% 50%,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 100%,
                    var(--quran-wird-frame-cut-inner) 100%,
                    0 50%);
            overflow: hidden;
            pointer-events: none;
            z-index: 3;
            opacity: 0;
        }

        .quran-wird-progress-hover-shimmer::before {
            content: '';
            position: absolute;
            inset-block: 0;
            inset-inline-start: -46%;
            width: 38%;
            background: linear-gradient(112deg,
                    rgb(255 255 255 / 0%) 0%,
                    rgb(255 255 255 / 16%) 28%,
                    rgb(255 255 255 / 65%) 52%,
                    rgb(255 255 255 / 16%) 74%,
                    rgb(255 255 255 / 0%) 100%);
            filter: blur(0.5px);
        }

        .quran-wird-progress-button--shimmer-running .quran-wird-progress-hover-shimmer {
            opacity: 1;
        }

        .quran-wird-progress-button--shimmer-running .quran-wird-progress-hover-shimmer::before {
            animation: quran-wird-hover-shimmer-pass 1.18s cubic-bezier(0.24, 0.75, 0.3, 1) 1;
        }

        .quran-wird-progress-fill {
            position: absolute;
            inset-block: 0.16rem;
            inset-inline-start: 0;
            clip-path: polygon(var(--quran-wird-frame-cut-inner) 0,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 0,
                    100% 50%,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 100%,
                    var(--quran-wird-frame-cut-inner) 100%,
                    0 50%);
            transition: width 420ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        .quran-wird-progress-fill--committed {
            width: var(--quran-wird-progress-percent);
            background: linear-gradient(90deg,
                    color-mix(in srgb, var(--success-600) 74%, transparent),
                    color-mix(in srgb, var(--success-400) 58%, transparent));
            z-index: 1;
        }

        .quran-wird-progress-fill--browse {
            width: var(--quran-wird-progress-browse-percent);
            inset-block: 0.16rem;
            background: linear-gradient(90deg,
                    color-mix(in srgb, white 86%, var(--gray-100) 14%),
                    color-mix(in srgb, var(--gray-200) 56%, transparent));
            opacity: 0.34;
            z-index: 2;
        }

        .quran-wird-progress-button.quran-wird-progress-button--completed {
            box-shadow: 0 10px 20px color-mix(in srgb, var(--success-900) 16%, transparent);
        }

        .quran-reader--visual-enhancements-disabled .quran-wird-progress-aura-water,
        .quran-reader--visual-enhancements-disabled .quran-wird-progress-aura-reflect,
        .quran-reader--visual-enhancements-disabled .quran-wird-progress-aura-rainbow,
        .quran-reader--visual-enhancements-disabled .quran-wird-progress-hover-shimmer {
            display: none;
        }

        @keyframes quran-wird-aura-water-flow {
            0% {
                background-position: 12% 28%, 86% 74%, 54% 38%;
                opacity: 1;
            }

            50% {
                background-position: 34% 76%, 22% 22%, 66% 64%;
                opacity: 0;
            }

            100% {
                background-position: 12% 28%, 86% 74%, 54% 38%;
                opacity: 1;
            }
        }

        @keyframes quran-wird-aura-water-breathe {
            0% {
                filter: saturate(1.1) brightness(0.98);
                opacity: 1;
            }

            55% {
                filter: saturate(1.34) brightness(1.05);
                opacity: 0;
            }

            100% {
                filter: saturate(1.1) brightness(0.98);
                opacity: 1;
            }
        }

        @keyframes quran-wird-rainbow-glow {
            0% {
                background-position: 0% 50%;
            }

            100% {
                background-position: 200% 50%;
            }
        }

        @keyframes quran-wird-hover-shimmer-pass {
            0% {
                inset-inline-start: -46%;
                opacity: 0;
            }

            16% {
                opacity: 1;
            }

            100% {
                inset-inline-start: 112%;
                opacity: 0;
            }
        }

        .quran-wird-progress-content {
            position: relative;
            z-index: 4;
            display: flex;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 0.52rem;
            padding-inline: 0.98rem;
            line-height: 1;
            font-family: 'IBM Plex Sans Arabic', 'Readex Pro', ui-sans-serif, system-ui, sans-serif;
            direction: ltr;
        }

        .quran-wird-progress-percent {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--success-800) 42%, transparent);
            background: color-mix(in srgb, var(--success-100) 56%, transparent);
            color: color-mix(in srgb, var(--success-900) 84%, var(--quran-panel-text));
            font-weight: 800;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .quran-wird-progress-count {
            font-weight: 900;
            letter-spacing: 0.01em;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .quran-top-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.15rem;
            height: 2.15rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--primary-500) 52%, transparent);
            background: color-mix(in srgb, var(--quran-chip-bg) 90%, transparent);
            color: color-mix(in srgb, var(--quran-panel-text) 88%, var(--primary-500));
            transition:
                transform 160ms ease,
                border-color 220ms ease,
                background-color 220ms ease,
                box-shadow 220ms ease;
            box-shadow: 0 8px 18px color-mix(in srgb, var(--gray-900) 16%, transparent);
        }

        .quran-top-action:hover {
            border-color: color-mix(in srgb, var(--primary-500) 72%, transparent);
            background: color-mix(in srgb, var(--quran-chip-hover) 70%, transparent);
        }

        .quran-top-action:active {
            transform: scale(0.96);
        }

        .quran-top-action.quran-top-action--active {
            border-color: color-mix(in srgb, var(--warning-500) 72%, transparent);
            background: color-mix(in srgb, var(--warning-300) 28%, transparent);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--warning-200) 74%, transparent),
                0 10px 20px color-mix(in srgb, var(--warning-700) 18%, transparent);
            color: color-mix(in srgb, var(--warning-800) 92%, var(--quran-panel-text));
        }

        #quran-reader-history-toggle.quran-history-toggle-button {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: color-mix(in srgb, var(--primary-600) 74%, var(--primary-500));
            border-radius: 999px;
            cursor: pointer;
            transition-duration: 0.3s;
            box-shadow: 2px 2px 10px rgb(0 0 0 / 13%);
            border: none;
            color: #fff;
        }

        .dark #quran-reader-history-toggle.quran-history-toggle-button {
            background-color: color-mix(in srgb, var(--gray-200) 80%, var(--gray-300));
        }

        #quran-reader-history-toggle .quran-history-toggle-icon {
            transform: rotate(0deg);
            transition: transform 460ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        #quran-reader-history-toggle .quran-history-toggle-icon path {
            stroke: #fff;
            fill: transparent;
        }

        .dark #quran-reader-history-toggle .quran-history-toggle-icon path {
            stroke: var(--gray-900);
        }

        .dark #quran-reader-history-toggle.quran-history-toggle-button:hover .quran-history-toggle-icon path {
            stroke: var(--gray-900);
        }

        #quran-reader-history-toggle.quran-history-toggle-button:hover {
            background-color: color-mix(in srgb, var(--primary-500) 68%, var(--primary-400));
        }

        .dark #quran-reader-history-toggle.quran-history-toggle-button:hover {
            background-color: color-mix(in srgb, var(--gray-100) 90%, var(--gray-300));
        }

        #quran-reader-history-toggle.quran-history-toggle-button:hover .quran-history-toggle-icon {
            transform: rotate(360deg);
        }

        #quran-reader-history-toggle.quran-history-toggle-button:active {
            transform: scale(0.8);
        }

        #quran-reader-bookmark-toggle.quran-bookmark-toggle-button {
            position: relative;
            overflow: hidden;
            cursor: pointer;
            background-color: color-mix(in srgb, var(--warning-600) 90%, var(--warning-700));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            border: 0;
            transition:
                transform 160ms ease,
                background-color 220ms ease,
                box-shadow 220ms ease;
        }

        .dark #quran-reader-bookmark-toggle.quran-bookmark-toggle-button {
            background-color: color-mix(in srgb, var(--warning-400) 10%, var(--warning-600));
        }

        #quran-reader-bookmark-toggle.quran-bookmark-toggle-button:hover {
            background-color: color-mix(in srgb, var(--warning-500) 50%, var(--warning-600));
        }

        #quran-reader-bookmark-toggle.quran-bookmark-toggle-button:focus-visible {
            box-shadow:
                0 0 0 2px color-mix(in srgb, var(--quran-page-bg) 76%, transparent),
                0 0 0 4px color-mix(in srgb, var(--warning-800) 80%, transparent);
        }

        #quran-reader-bookmark-toggle .quran-bookmark-toggle-fill {
            position: absolute;
            width: 0.5rem;
            height: 0.5rem;
            background: rgb(255 255 255 / 25%);
            border-radius: 999px;
            transform: scale(0);
            pointer-events: none;
            z-index: 1;
        }

        #quran-reader-bookmark-toggle.quran-bookmark-toggle-button:active .quran-bookmark-toggle-fill {
            animation: quran-bookmark-fill-spread 2s linear forwards;
        }

        #quran-reader-bookmark-toggle.quran-bookmark-toggle-button:not(:active) .quran-bookmark-toggle-fill {
            animation: quran-bookmark-fill-retract 0.3s ease forwards;
        }

        #quran-reader-bookmark-toggle .quran-bookmark-toggle-icon {
            position: relative;
            z-index: 2;
            height: auto;
        }

        #quran-reader-bookmark-toggle .quran-bookmark-toggle-icon path {
            stroke-dasharray: 200 0;
            stroke-dashoffset: 0;
            stroke: #fff;
            fill: transparent;
            transition: 0.5s;
        }

        #quran-reader-bookmark-toggle.quran-bookmark-toggle-button.quran-bookmark-toggle-button--bookmarked .quran-bookmark-toggle-icon path {
            fill: #fff;
            animation: quran-bookmark-draw 0.5s linear;
            transition-delay: 0.5s;
        }

        @keyframes quran-bookmark-fill-spread {
            from {
                transform: scale(0);
                opacity: 0.6;
            }

            to {
                transform: scale(15);
                opacity: 1;
            }
        }

        @keyframes quran-bookmark-fill-retract {
            from {
                transform: scale(15);
                opacity: 1;
            }

            to {
                transform: scale(0);
                opacity: 0;
            }
        }

        @keyframes quran-bookmark-draw {
            0% {
                stroke-dasharray: 0 200;
                stroke-dashoffset: 80;
            }

            100% {
                stroke-dasharray: 200 0;
            }
        }

        .quran-copy-popover {
            position: fixed;
            z-index: 90;
            pointer-events: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.28rem;
            transform: translate(-50%, -132%);
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--primary-500) 54%, transparent);
            background: color-mix(in srgb, var(--background) 88%, transparent);
            color: color-mix(in srgb, var(--primary-900) 92%, var(--quran-panel-text));
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--primary-100) 24%, transparent),
                0 8px 18px color-mix(in srgb, var(--gray-900) 20%, transparent);
            padding: 0.26rem 0.56rem;
            font-family: 'IBM Plex Sans Arabic', 'Manrope', ui-sans-serif, system-ui, sans-serif;
            font-size: 0.74rem;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
            direction: rtl;
        }

        .quran-copy-popover-enter {
            transition:
                opacity 180ms cubic-bezier(0.2, 0.75, 0.25, 1),
                transform 180ms cubic-bezier(0.2, 0.75, 0.25, 1);
        }

        .quran-copy-popover-enter-start {
            opacity: 0;
            transform: translate(-50%, -118%) scale(0.84);
        }

        .quran-copy-popover-enter-end {
            opacity: 1;
            transform: translate(-50%, -132%) scale(1);
        }

        .quran-copy-popover-leave {
            transition:
                opacity 220ms cubic-bezier(0.4, 0, 1, 1),
                transform 220ms cubic-bezier(0.4, 0, 1, 1);
        }

        .quran-copy-popover-leave-start {
            opacity: 1;
            transform: translate(-50%, -132%) scale(1);
        }

        .quran-copy-popover-leave-end {
            opacity: 0;
            transform: translate(-50%, -156%) scale(1);
        }

        .quran-soorah-trigger {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            background: transparent;
            border: 0.14rem solid transparent;
            border-radius: 999px;
            color: color-mix(in srgb, var(--primary-700) 86%, var(--quran-panel-text));
            cursor: pointer;
            overflow: hidden;
            direction: rtl;
            transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
            box-shadow: 0 0 0 1.6px color-mix(in srgb, var(--primary-500) 72%, transparent);
            font-family: 'Readex Pro', 'IBM Plex Sans Arabic', 'Noto Naskh Arabic', ui-sans-serif, system-ui, sans-serif;
            font-weight: 700;
            line-height: 1;
            user-select: none;
            -webkit-user-select: none;
        }

        .quran-soorah-trigger-shell {
            position: relative;
            isolation: isolate;
            z-index: 160;
        }

        .quran-soorah-quick-nav {
            position: absolute;
            inset-block-start: 50%;
            inline-size: 0;
            block-size: 0;
            transform: translate(-50%, -50%) scale(0.9);
            transform-origin: 50% 50%;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition:
                opacity 200ms cubic-bezier(0.22, 1, 0.36, 1),
                transform 240ms cubic-bezier(0.22, 1, 0.36, 1),
                visibility 0ms linear 240ms;
            z-index: 200;
        }

        .quran-soorah-quick-nav.quran-soorah-quick-nav--visible {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translate(-50%, -50%) scale(1);
            transition-delay: 0ms;
        }

        .quran-soorah-quick-nav-button {
            position: absolute;
            inset-inline-start: 50%;
            border-radius: 999px;
            border: 0.08rem solid color-mix(in srgb, var(--primary-300) 72%, transparent);
            background:
                radial-gradient(110% 130% at 50% 0%,
                    color-mix(in srgb, var(--primary-50) 90%, white) 0%,
                    color-mix(in srgb, var(--primary-200) 34%, white) 65%,
                    color-mix(in srgb, var(--primary-300) 24%, white) 100%);
            box-shadow:
                0 0.2rem 0.68rem color-mix(in srgb, var(--primary-700) 24%, transparent),
                inset 0 0.04rem 0.22rem color-mix(in srgb, white 68%, transparent);
            color: color-mix(in srgb, var(--primary-700) 85%, var(--quran-panel-text));
            display: grid;
            place-items: center;
            outline: none;
            transition:
                transform 160ms ease,
                box-shadow 160ms ease,
                color 160ms ease,
                opacity 160ms ease;
        }

        .dark .quran-soorah-quick-nav-button {
            border-color: color-mix(in srgb, var(--primary-200) 48%, transparent);
            background:
                radial-gradient(120% 130% at 50% 0%,
                    color-mix(in srgb, var(--primary-900) 46%, var(--gray-900)) 0%,
                    color-mix(in srgb, var(--primary-800) 65%, var(--gray-900)) 65%,
                    color-mix(in srgb, var(--primary-700) 68%, var(--gray-900)) 100%);
            color: color-mix(in srgb, var(--primary-100) 85%, white);
            box-shadow:
                0 0.24rem 0.72rem color-mix(in srgb, black 40%, transparent),
                inset 0 0.03rem 0.26rem color-mix(in srgb, white 14%, transparent);
        }

        .quran-soorah-quick-nav-button:not(:disabled):hover,
        .quran-soorah-quick-nav-button:not(:disabled):focus-visible {
            box-shadow:
                0 0.36rem 0.84rem color-mix(in srgb, var(--primary-700) 28%, transparent),
                inset 0 0.05rem 0.24rem color-mix(in srgb, white 74%, transparent);
        }

        .quran-soorah-quick-nav-button:disabled {
            opacity: 0.46;
            cursor: not-allowed;
            box-shadow:
                0 0.1rem 0.38rem color-mix(in srgb, var(--gray-900) 14%, transparent),
                inset 0 0 0 0.06rem color-mix(in srgb, var(--gray-500) 32%, transparent);
        }

        .quran-soorah-quick-nav-arrow {
            fill: currentColor;
        }

        .dark .quran-soorah-trigger {
            color: color-mix(in srgb, var(--primary-200) 86%, var(--quran-panel-text));
            box-shadow: 0 0 0 1.6px color-mix(in srgb, var(--primary-200) 72%, transparent);
        }

        .quran-soorah-trigger.quran-soorah-trigger--disabled {
            opacity: 0.55;
            cursor: not-allowed;
            box-shadow: 0 0 0 1.4px color-mix(in srgb, var(--gray-500) 52%, transparent);
            transform: none;
        }

        .quran-soorah-trigger.quran-soorah-trigger--disabled .quran-soorah-trigger-icon {
            opacity: 0.44;
            /* transform: translateX(0) scale(0.92); */
        }

        .quran-soorah-trigger.quran-soorah-trigger--disabled .quran-soorah-trigger-circle {
            opacity: 0;
            /* transform: translate(-50%, -50%) scale(0); */
        }

        .quran-soorah-trigger-icon {
            position: absolute;
            z-index: 3;
            stroke: currentColor;
            stroke-width: 3;
            fill: none;
            opacity: 0;
            transform: translateX(0.4rem) scale(0.86);
            transition:
                inset-inline-start 0.8s cubic-bezier(0.23, 1, 0.32, 1),
                transform 0.8s cubic-bezier(0.23, 1, 0.32, 1),
                opacity 0.8s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .quran-soorah-trigger-circle {
            position: absolute;
            inset-block-start: 50%;
            inset-inline-start: 50%;
            transform: translate(-50%, -50%) scale(0);
            border-radius: 999px;
            opacity: 0.25;
            z-index: 1;
            background: color-mix(in srgb, var(--primary-600) 92%, var(--primary-400));
            transition: all 0.7s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .dark .quran-soorah-trigger-circle {
            background: color-mix(in srgb, var(--primary-400) 92%, var(--primary-300));
        }

        .quran-soorah-trigger-text {
            position: relative;
            z-index: 2;
            transform: translateX(0.2rem);
            transition:
                transform 0.8s cubic-bezier(0.23, 1, 0.32, 1),
                color 0.8s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .quran-soorah-trigger-text-inner {
            display: inline-block;
            white-space: nowrap;
        }

        .quran-soorah-trigger-text-inner.quran-caption-leave-forward {
            animation: quran-caption-leave-forward 140ms ease both;
        }

        .quran-soorah-trigger-text-inner.quran-caption-leave-backward {
            animation: quran-caption-leave-backward 140ms ease both;
        }

        .quran-soorah-trigger-text-inner.quran-caption-enter-forward {
            animation: quran-caption-enter-forward 180ms ease both;
        }

        .quran-soorah-trigger-text-inner.quran-caption-enter-backward {
            animation: quran-caption-enter-backward 180ms ease both;
        }

        .quran-reader--wird-active .quran-soorah-trigger,
        .quran-reader--wird-active .quran-soorah-trigger-icon,
        .quran-reader--wird-active .quran-soorah-trigger-circle,
        .quran-reader--wird-active .quran-soorah-trigger-text,
        .quran-reader--wird-active .quran-soorah-trigger-text-inner {
            transition: none;
            animation: none;
        }

        .quran-reader--wird-active .quran-soorah-quick-nav {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        @keyframes quran-caption-leave-forward {
            from {
                opacity: 1;
                transform: translateX(0);
            }

            to {
                opacity: 0;
                transform: translateX(0.5rem);
            }
        }

        @keyframes quran-caption-leave-backward {
            from {
                opacity: 1;
                transform: translateX(0);
            }

            to {
                opacity: 0;
                transform: translateX(-0.5rem);
            }
        }

        @keyframes quran-caption-enter-forward {
            from {
                opacity: 0;
                transform: translateX(-0.5rem);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes quran-caption-enter-backward {
            from {
                opacity: 0;
                transform: translateX(0.5rem);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .quran-top-strip:not(.quran-top-strip--wird-active) .quran-soorah-trigger:hover {
            color: color-mix(in srgb, var(--primary-50) 92%, var(--gray-900));
            box-shadow: 0 0 0 0.75rem transparent;
        }

        .quran-top-strip:not(.quran-top-strip--wird-active) .quran-soorah-trigger:hover .quran-soorah-trigger-icon {
            inset-inline-start: 1.25rem;
            opacity: 1;
            transform: translateX(-0.2rem) scale(1);
        }

        @media (min-width: 1280px) and (max-width: 1535px) {
            .quran-top-strip:not(.quran-top-strip--wird-active) .quran-soorah-trigger:hover .quran-soorah-trigger-icon {
                transform: translateX(0.1rem) scale(1);
            }
        }

        @media (min-width: 1536px) and (max-width: 1919px) {
            .quran-top-strip:not(.quran-top-strip--wird-active) .quran-soorah-trigger:hover .quran-soorah-trigger-icon {
                transform: translateX(0.15rem) scale(1);
            }
        }

        .quran-top-strip:not(.quran-top-strip--wird-active) .quran-soorah-trigger:hover .quran-soorah-trigger-text {
            transform: translateX(-0.62rem);
        }

        .quran-top-strip:not(.quran-top-strip--wird-active) .quran-soorah-trigger:hover .quran-soorah-trigger-circle {
            transform: translate(-50%, -50%) scale(18);
            opacity: 1;
        }

        .quran-top-strip:not(.quran-top-strip--wird-active) .quran-soorah-trigger:active {
            transform: scale(0.97);
            box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--primary-500) 58%, transparent);
        }

        .dark .quran-top-strip:not(.quran-top-strip--wird-active) .quran-soorah-trigger:active {
            box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--primary-300) 58%, transparent);
        }

        .quran-reader-panel--calibrating .quran-top-strip {
            filter: none;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition:
                opacity 180ms ease,
                visibility 0ms linear 180ms;
        }

        .quran-reader-panel--calibrating .quran-bottom-strip {
            filter: none;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: all 180ms ease;
        }

        .quran-bottom-strip {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            grid-template-rows: auto auto;
            align-items: center;
            padding-top: 0.45rem;
            padding-right: 1rem;
            padding-bottom: 0.74rem;
            padding-left: 1rem;
            min-height: 3.65rem;
            position: relative;
            isolation: isolate;
            overflow: hidden;
        }

        @media (min-width: 1920px) and (max-width: 2559px) {
            .quran-bottom-strip {
                padding-top: 0;
            }
        }

        .quran-bottom-strip::before {
            content: '';
            position: absolute;
            inset: 0;
            /* background: linear-gradient(to top,rgb(255 255 255 / 0.98) 0%,rgb(255 255 255 / 0.84) 48%,rgb(255 255 255 / 0.15) 74%,transparent 100%); */
            opacity: 0;
            transition: opacity 200ms ease;
            pointer-events: none;
            z-index: 0;
        }

        .quran-bottom-strip>* {
            position: relative;
            z-index: 1;
            transition: all 180ms ease;
        }

        .quran-reader-panel--calibrating .quran-bottom-strip::before {
            opacity: 1;
        }

        .quran-reader-panel--calibrating .quran-bottom-strip>* {
            filter: blur(2.2px);
            opacity: 0.36;
            pointer-events: none;
        }

        .quran-bottom-strip-nav-prev {
            grid-column: 1;
            grid-row: 1 / span 2;
            justify-self: center;
            align-self: center;
        }

        .quran-bottom-strip-nav-next {
            grid-column: 3;
            grid-row: 1 / span 2;
            justify-self: center;
            align-self: center;
        }

        .quran-bottom-strip-center {
            grid-column: 2;
            grid-row: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .quran-bottom-strip-slider {
            grid-column: 2;
            grid-row: 2;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .quran-page-counter {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.36rem;
        }

        .quran-page-slider {
            appearance: none;
            -webkit-appearance: none;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--gray-500) 50%, transparent);
            background: linear-gradient(90deg,
                    color-mix(in srgb, var(--primary-500) 62%, transparent),
                    color-mix(in srgb, var(--primary-300) 44%, transparent));
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--gray-500) 18%, transparent),
                0 5px 12px color-mix(in srgb, var(--gray-900) 16%, transparent);
            cursor: pointer;
        }

        .dark .quran-page-slider {
            background: linear-gradient(90deg, color-mix(in srgb, var(--primary-200) 50%, transparent), color-mix(in srgb, var(--primary-300) 50%, transparent));
        }

        .quran-page-slider:disabled {
            cursor: not-allowed;
            filter: saturate(0.2);
            opacity: 0.62;
        }

        .quran-page-slider::-webkit-slider-thumb {
            appearance: none;
            -webkit-appearance: none;
            width: 0.95rem;
            height: 0.95rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--primary-600) 72%, transparent);
            background: linear-gradient(180deg,
                    color-mix(in srgb, var(--primary-100) 92%, white),
                    color-mix(in srgb, var(--primary-200) 80%, var(--primary-50)));
            box-shadow: 0 4px 10px color-mix(in srgb, var(--primary-800) 20%, transparent);
        }

        .quran-page-slider:disabled::-webkit-slider-thumb,
        .quran-page-slider:disabled::-moz-range-thumb {
            border-color: color-mix(in srgb, var(--gray-500) 55%, transparent);
            box-shadow: none;
        }

        .quran-page-slider::-moz-range-thumb {
            width: 0.95rem;
            height: 0.95rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--primary-600) 72%, transparent);
            background: linear-gradient(180deg,
                    color-mix(in srgb, var(--primary-100) 92%, white),
                    color-mix(in srgb, var(--primary-200) 80%, var(--primary-50)));
            box-shadow: 0 4px 10px color-mix(in srgb, var(--primary-800) 20%, transparent);
        }

        .quran-page-slider-chip {
            position: relative;
            --quran-counter-digit-width: 1ch;
            --quran-counter-digit-count: 3;
            --quran-counter-track-width: calc(var(--quran-counter-digit-width) * var(--quran-counter-digit-count));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.12rem;
            border: 1px solid color-mix(in srgb, var(--gray-500) 52%, transparent);
            background: linear-gradient(176deg,
                    color-mix(in srgb, var(--gray-200) 70%, transparent),
                    color-mix(in srgb, var(--gray-300) 58%, transparent));
            cursor: pointer;
            font-family: 'IBM Plex Sans Arabic', 'Manrope', ui-sans-serif, system-ui, sans-serif;
            font-weight: 600;
            color: var(--quran-panel-text);
            line-height: 1;
            font-variant-numeric: tabular-nums;
            direction: ltr;
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--gray-500) 12%, transparent),
                0 6px 14px color-mix(in srgb, var(--gray-800) 16%, transparent);
            transition:
                transform 140ms ease,
                box-shadow 180ms ease,
                color 180ms ease;
        }

        .dark .quran-page-slider-chip {
            background: linear-gradient(176deg, color-mix(in srgb, var(--gray-100) 30%, transparent), color-mix(in srgb, var(--gray-200) 40%, transparent));
        }

        .quran-page-slider-chip:hover {
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--primary-300) 24%, transparent),
                0 8px 16px color-mix(in srgb, var(--primary-800) 20%, transparent);
        }

        .quran-page-slider-chip:active {}

        .quran-page-slider-chip.quran-page-slider-chip--disabled {
            opacity: 0.68;
            cursor: not-allowed;
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--gray-500) 8%, transparent),
                0 4px 10px color-mix(in srgb, var(--gray-900) 12%, transparent);
        }

        .quran-page-slider-chip.quran-page-slider-chip--mushaf-reference {
            pointer-events: none;
            cursor: default;
        }

        .quran-page-chip-label {
            opacity: 0.8;
            font-size: 0.92em;
            margin-inline-end: 0.16rem;
        }

        .quran-page-chip-current-wrap {
            position: relative;
            display: inline-grid;
            place-items: center;
            min-width: var(--quran-counter-track-width);
        }

        .quran-page-chip-current {
            display: inline-grid;
            grid-auto-flow: column;
            grid-auto-columns: var(--quran-counter-digit-width);
            align-items: center;
            justify-content: center;
            min-width: var(--quran-counter-track-width);
            width: var(--quran-counter-track-width);
        }

        .quran-page-chip-total {
            display: inline-grid;
            grid-auto-flow: column;
            grid-auto-columns: var(--quran-counter-digit-width);
            align-items: center;
            justify-content: center;
            min-width: var(--quran-counter-track-width);
            width: var(--quran-counter-track-width);
            opacity: 0.88;
        }

        .quran-page-chip-separator {
            opacity: 0.65;
        }

        .quran-page-counter-morph {
            position: absolute;
            inset: 0;
            display: inline-grid;
            grid-auto-flow: column;
            grid-auto-columns: var(--quran-counter-digit-width);
            align-items: center;
            justify-content: center;
            min-width: var(--quran-counter-track-width);
            width: var(--quran-counter-track-width);
            pointer-events: none;
            z-index: 2;
        }

        .quran-page-slider-chip,
        .quran-page-chip-current,
        .quran-page-chip-total,
        .quran-page-counter-morph,
        .quran-counter-cell,
        .quran-counter-roll__prev,
        .quran-counter-roll__next {
            font-variant-numeric: tabular-nums lining-nums;
            font-feature-settings: 'tnum' 1, 'lnum' 1;
            font-kerning: none;
            letter-spacing: 0;
            white-space: nowrap;
        }

        .quran-page-counter.quran-page-counter--morphing .quran-page-chip-current {
            color: transparent;
        }

        .quran-counter-roll {
            display: grid;
            place-items: center;
            contain: layout style;
            width: 100%;
        }

        .quran-counter-cell {
            display: inline-grid;
            place-items: center;
            width: var(--quran-counter-digit-width);
        }

        .quran-counter-static {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .quran-counter-roll__prev,
        .quran-counter-roll__next {
            grid-area: 1 / 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            will-change: transform, opacity;
        }

        .quran-counter-roll__prev {
            animation: quran-counter-prev 520ms ease-out both;
        }

        .quran-counter-roll__next {
            animation: quran-counter-next 520ms ease-out both;
        }

        .quran-swipe-hint {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.2rem;
            align-self: center;
            color: color-mix(in srgb, var(--quran-subtle) 86%, transparent);
            opacity: 0.88;
        }

        .quran-swipe-hint-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            background: transparent;
            cursor: pointer;
            transition:
                opacity 160ms ease,
                transform 160ms ease,
                color 160ms ease;
        }

        .quran-swipe-hint-button:hover {
            opacity: 1;
            transform: translateY(-0.04rem);
            color: color-mix(in srgb, var(--quran-panel-text) 92%, var(--quran-subtle));
        }

        .quran-swipe-hint-button:active {
            transform: translateY(0);
        }

        .quran-swipe-hint-button:disabled {
            cursor: default;
            opacity: 0.48;
            transform: none;
            pointer-events: none;
        }

        .quran-swipe-hint-chev {
            color: color-mix(in srgb, var(--warning-400) 70%, var(--warning-600) 80%);
            display: inline-block;
            animation: quran-swipe-shimmer 1400ms ease-in-out infinite;
            line-height: 1;
            vertical-align: middle;
            position: relative;
            top: 0;
            transition: color 200ms ease;
        }

        .quran-swipe-hint-chev.quran-swipe-hint-chev-static {
            animation: none !important;
            transform: none !important;
        }

        .quran-swipe-hint-button:disabled .quran-swipe-hint-chev,
        .quran-swipe-hint-button:disabled .quran-swipe-hint-chev.quran-swipe-hint-chev-opposite {
            color: color-mix(in srgb, var(--gray-500) 82%, var(--gray-400));
        }

        .quran-swipe-hint-chev:nth-child(1) {
            animation-delay: 330ms;
        }

        .quran-swipe-hint-chev:nth-child(2) {
            animation-delay: 220ms;
        }

        .quran-swipe-hint-chev:nth-child(3) {
            animation-delay: 110ms;
        }

        .quran-swipe-hint-chev.quran-swipe-hint-chev-opposite {
            animation: quran-swipe-shimmer-opposite 1400ms ease-in-out infinite;
        }

        .quran-swipe-hint-chev.quran-swipe-hint-chev-opposite:nth-child(1) {
            animation-delay: 710ms;
        }

        .quran-swipe-hint-chev.quran-swipe-hint-chev-opposite:nth-child(2) {
            animation-delay: 820ms;
        }

        .quran-swipe-hint-chev.quran-swipe-hint-chev-opposite:nth-child(3) {
            animation-delay: 930ms;
        }

        .quran-reader--visual-enhancements-disabled .quran-swipe-hint-chev,
        .quran-reader--visual-enhancements-disabled .quran-swipe-hint-chev.quran-swipe-hint-chev-opposite {
            animation: none !important;
            opacity: 0.7;
            transform: none;
        }

        @keyframes quran-swipe-shimmer {

            0%,
            100% {
                opacity: 0.22;
                transform: translateX(0);
            }

            50% {
                opacity: 1;
                transform: translateX(0.1rem);
            }
        }

        @keyframes quran-swipe-shimmer-opposite {

            0%,
            100% {
                opacity: 1;
                transform: translateX(-0.1rem);
            }

            50% {
                opacity: 0.22;
                transform: translateX(0);
            }
        }

        @keyframes quran-page-slide-next {
            from {
                opacity: 0.46;
                transform: translateY(0.5rem);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes quran-page-slide-prev {
            from {
                opacity: 0.46;
                transform: translateY(0.5rem);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes quran-counter-prev {
            0% {
                opacity: 1;
                transform: translateY(0);
            }

            100% {
                opacity: 0;
                transform: translateY(-8px);
            }
        }

        @keyframes quran-counter-next {
            0% {
                opacity: 0;
                transform: translateY(8px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endassets

@php
    $sooraHeaderTopPadding =
        'calc(var(--quran-line-gap) * var(--quran-gap-scale) * var(--quran-page-gap-multiplier, 1) * 0.38)';

    $initialReaderPayload = [
        'ready' => $ready,
        'pageNumber' => $pageNumber,
        'maxPage' => $maxPage,
        'activeAyahIndex' => $activeAyahIndex,
        'mushafLines' => $mushafLines,
        'qpcPageFontFamily' => $qpcPageFontFamily,
        'qpcPageFontUrl' => $qpcPageFontUrl,
        'qpcPageFontFormat' => $qpcPageFontFormat,
        'basmallahFontFamily' => $basmallahFontFamily,
        'basmallahFontUrl' => $basmallahFontUrl,
        'basmallahFontFormat' => $basmallahFontFormat,
        'basmallahText' => $basmallahText,
        'surahHeaderFontFamily' => $surahHeaderFontFamily,
        'surahHeaderFontUrl' => $surahHeaderFontUrl,
        'surahHeaderFontFormat' => $surahHeaderFontFormat,
        'surahHeaderTopPaddingWhenFollowingPreviousSurahAyah' => $sooraHeaderTopPadding,
        'surahNames' => $surahNames ?? [],
        'surahDirectory' => $surahDirectory ?? [],
        'useCenteredAyahLayout' => $useCenteredAyahLayout,
    ];
@endphp

<div
    data-quran-app-reader-root
    x-data="quranAppReader({
        api: {
            pageDataTemplate: @js(url('/quran-reader/pages/__PAGE__.json')),
            searchIndexUrl: @js(url('/quran-reader/search-index.json')),
        },
        initialPayload: @js($initialReaderPayload),
        nativeRuntime: @js(is_platform('native')),
        prewarmPages: @js(is_platform('native') ? 12 : 6),
        prefetchRadius: @js(is_platform('native') ? 3 : 2),
        searchModalId: @js('quran-reader-search-modal'),
        searchModalDomId: @js('quran-reader-search-modal'),
        searchActionModalId: @js(''),
        jumpPageModalId: @js('quran-reader-jump-page-modal'),
        historyModalId: @js('quran-reader-history-modal'),
        bookmarksModalId: @js('quran-reader-bookmarks-modal'),
        settings: @js($quranReaderSettings ?? ['enableVisualEnhancements' => false, 'targetWordsByDefault' => false, 'preserveHarakatOnCopy' => true, 'appendSurahAffixOnMultiCopy' => true, 'appendSurahAffixAlwaysOnCopy' => false, 'useVolumeButtonsNavigation' => false, 'useWesternNumerals' => true, 'wirdFrequencyMode' => 0, 'wirdKhatmatTarget' => 1]),
    })"
>
    <div
        class="quran-reader relative grid h-full w-full place-items-center items-center"
        dir="rtl"
        x-bind:class="{
            'quran-reader--visual-enhancements-disabled': !doesEnableVisualEnhancements,
            'quran-reader--wird-active': wirdModeActive,
        }"
        x-on:control-panel-updated.window="applyControlPanelSettings($event.detail?.controlPanel ?? {})"
        x-on:switch-view.window="$nextTick(() => syncNativeVolumeNavigation())"
        x-on:quran-bootstrap-request.window="prepareQuranFromMainMenu($event.detail ?? {})"
        x-on:open-modal.window="handleModalLifecycleEvent('opened', $event)"
        x-on:x-modal-opened.window="handleModalLifecycleEvent('opened', $event)"
        x-on:close-modal.window="handleModalLifecycleEvent('closing', $event)"
        x-on:close-modal-quietly.window="handleModalLifecycleEvent('closing', $event)"
        x-on:x-modal-closed.window="handleModalLifecycleEvent('closed', $event)"
        x-on:opened-form-component-action-modal.window="handleModalLifecycleEvent('opened', $event)"
        x-on:closing-form-component-action-modal.window="handleModalLifecycleEvent('closing', $event)"
        x-on:closed-form-component-action-modal.window="handleModalLifecycleEvent('closed', $event)"
        x-on:support-unlock-updated.window="applySupportUnlockDecision($event.detail?.mode ?? null)"
        x-on:quran-history-manager-go.window="handleHistoryManagerGoEvent($event.detail ?? {})"
        x-on:quran-history-manager-updated.window="applyHistoryManagerRecordUpdate($event.detail ?? {})"
        x-on:quran-history-manager-removed.window="removeHistoryEntry($event.detail?.id)"
        x-on:quran-history-manager-clear-untagged.window="clearNavigationHistory()"
        x-on:quran-history-manager-reordered.window="applyHistoryManagerReorder($event.detail ?? {})"
        x-on:quran-bookmarks-manager-go.window="handleBookmarksManagerGoEvent($event.detail ?? {})"
        x-on:quran-bookmarks-manager-updated.window="applyBookmarkManagerRecordUpdate($event.detail ?? {})"
        x-on:quran-bookmarks-manager-replaced.window="replaceBookmarkPage($event.detail?.id)"
        x-on:quran-bookmarks-manager-removed.window="removeBookmark($event.detail?.id)"
        x-on:quran-bookmarks-manager-reordered.window="applyBookmarksManagerReorder($event.detail ?? {})"
        x-on:quran-history-manager-request-sync.window="syncHistoryManagerTableRecords()"
        x-on:quran-bookmarks-manager-request-sync.window="syncBookmarksManagerTableRecords()"
    >
        @if (!$ready)
            <section
                class="quran-reader-panel relative flex h-[clamp(28rem,82svh,50rem)] w-[min(94vw,50rem)] min-w-[18rem] flex-col items-center justify-center gap-4 rounded-[1.75rem] border px-6 py-7 text-center xl:rounded-[1.45rem] 2xl:rounded-[1.75rem]"
            >
                <h2 class="font-quran text-3xl leading-[1.9]">{{ arabic_text('قارئ القرآن') }}</h2>
                <p class="text-sm leading-7 opacity-85">
                    {{ arabic_text('بيانات المصحف غير متاحة بعد. تأكد من تجهيز جداول القرآن وبياناتها، ثم أعد فتح قسم الكتاب.') }}
                </p>
            </section>
        @else
            <section
                class="quran-reader-panel min-w-75 3xl:h-204 3xl:w-148 4xl:h-202 4xl:w-160 Xoverflow-hidden 2xl:w-124 2xl:h-166 xl:h-148 xl:w-108 relative flex h-[min(88svh,38rem)] w-[min(91vw,22rem)] flex-col rounded-t-2xl border sm:h-[min(82svh,50rem)] sm:w-[min(82vw,32rem)] sm:rounded-[1.75rem] md:h-[min(84svh,54rem)] md:w-[min(92vw,35rem)] lg:h-[min(92svh,44rem)] lg:w-[min(92vw,31rem)]"
                x-bind:style="readerPanelStyle()"
                x-bind:class="{ 'quran-reader-panel--calibrating': isCalibrating || _startupCalibrationPending }"
                x-on:pointerdown.passive="onSwipeStart($event)"
                x-on:pointermove.window.passive="onSwipeMove($event)"
                x-on:pointerup.window.passive="onSwipeEnd($event)"
                x-on:pointercancel.window.passive="onSwipeCancel()"
                x-on:touchstart.passive="onSwipeStart($event)"
                x-on:touchmove.window.passive="onSwipeMove($event)"
                x-on:touchend.window.passive="onSwipeEnd($event)"
                x-on:touchcancel.window.passive="onSwipeCancel()"
                x-on:keydown.left.window="onGlobalArrowNavigate('left', $event)"
                x-on:keydown.right.window="onGlobalArrowNavigate('right', $event)"
                x-on:quran-go-prev.window="handleRequestedNavigation('prev', $event.detail)"
                x-on:quran-go-next.window="handleRequestedNavigation('next', $event.detail)"
                x-on:quran-go-page.window="handleRequestedNavigation('page', $event.detail)"
                x-on:quran-go-gate.window="window.dispatchEvent(new CustomEvent('quran-reader-go-gate'))"
                x-ref="readerPanel"
            >
                <div
                    class="quran-calibration-overlay pointer-events-none absolute inset-0 z-30 grid place-items-center rounded-[1.75rem]"
                    wire:ignore
                    x-cloak
                    x-bind:class="{
                        'quran-calibration-overlay--visible': isCalibrating,
                        'quran-calibration-overlay--hidden': !isCalibrating,
                    }"
                    x-bind:aria-hidden="isCalibrating ? 'false' : 'true'"
                >
                </div>
                <template x-teleport="body">
                    <div
                        class="quran-calibration-hud"
                        wire:ignore
                        x-bind:class="{ 'quran-calibration-hud--visible': shouldShowCalibrationHud() }"
                        x-bind:style="calibrationHudStyle()"
                        x-bind:aria-hidden="shouldShowCalibrationHud() ? 'false' : 'true'"
                    >
                        <div
                            class="quran-calibration-loader 2xl:gap-4.5 flex flex-col items-center gap-3 sm:gap-3.5 md:gap-[0.85rem] lg:gap-3.5 xl:gap-4">
                            <div
                                class="quran-calibration-spinner 2xl:size-7.5 3xl:size-9 size-6 sm:size-7 lg:size-5 xl:size-7">
                                <svg
                                    class="quran-calibration-jelly-svg"
                                    aria-hidden="true"
                                    viewBox="0 0 30 30"
                                    preserveAspectRatio="xMidYMid meet"
                                    x-bind:style="`--uib-color: ${window.Alpine.store('colorScheme').isDark ? '#BFD4D7' : '#0a6571'}`"
                                >
                                    <defs>
                                        <filter
                                            id="quran-calibration-jelly-ooze"
                                            filterUnits="userSpaceOnUse"
                                            primitiveUnits="userSpaceOnUse"
                                            x="-8"
                                            y="-8"
                                            width="46"
                                            height="46"
                                        >
                                            <feGaussianBlur
                                                in="SourceGraphic"
                                                stdDeviation="3.333"
                                                result="blur"
                                            />
                                            {{-- blade-formatter-disable --}}
                                            <feColorMatrix
                                                values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 18 -7"
                                                in="blur"
                                                mode="matrix"
                                                result="ooze"
                                            />
                                            {{-- blade-formatter-enable --}}
                                            <feBlend
                                                in="SourceGraphic"
                                                in2="ooze"
                                            />
                                        </filter>
                                    </defs>
                                    <g class="quran-calibration-jelly-group">
                                        <circle
                                            class="quran-calibration-jelly-node quran-calibration-jelly-dot-top"
                                            cx="15"
                                            cy="5"
                                            r="5"
                                        />
                                        <circle
                                            class="quran-calibration-jelly-node quran-calibration-jelly-dot-right"
                                            cx="25"
                                            cy="25"
                                            r="5"
                                        />
                                        <circle
                                            class="quran-calibration-jelly-node quran-calibration-jelly-dot-left"
                                            cx="5"
                                            cy="25"
                                            r="5"
                                        />
                                        <circle
                                            class="quran-calibration-jelly-node quran-calibration-jelly-traveler"
                                            cx="15"
                                            cy="5"
                                            r="5"
                                        />
                                    </g>
                                </svg>
                            </div>
                            <span
                                class="font-arabic-sans 3xl:text-[1.2rem] text-[0.815rem] tracking-wide opacity-60 sm:text-[0.85rem] md:text-[0.8rem] lg:text-[0.82rem] xl:text-[0.87rem] 2xl:text-[0.94rem]"
                                dir="rtl"
                            >{{ arabic_text('تجهيز المصحف...') }}</span>
                        </div>
                    </div>
                </template>
                <header
                    class="quran-top-strip gap-[0.4rem] px-[0.6rem] pb-2 pt-[0.45rem] sm:gap-[0.65rem] sm:px-4 sm:pb-2 sm:pt-[0.8rem]"
                    x-bind:class="{
                        'quran-top-strip--wird-active': wirdModeActive,
                        'quran-top-strip--initial-loading': isCalibrating || _startupCalibrationPending || !
                            hasCompletedInitialMushafPreparation,
                        'quran-top-strip--visible': !isCalibrating && !_startupCalibrationPending &&
                            hasCompletedInitialMushafPreparation,
                    }"
                >
                    <!-- Credits: uiverse.io/gharsh11032000/loud-chicken-53 -->
                    <div
                        class="quran-soorah-trigger-shell"
                        data-no-swipe
                        x-on:pointerdown.outside="closeSurahQuickNavigator()"
                    >
                        <button
                            class="quran-soorah-trigger 3xl:w-[12.4rem] 4xl:w-[13.4rem] 4xl:px-[2.35rem] 4xl:py-[0.42rem] 4xl:text-[0.95rem] 4xl:min-h-[2.2rem] w-31 md:w-47 3xl:min-h-[2.15rem] 3xl:px-[2.28rem] 2xl:w-35 3xl:text-[0.93rem] lg:w-42 min-h-[1.95rem] shrink-0 px-[1.7rem] py-[0.34rem] text-[0.7rem] outline-none sm:min-h-8 sm:w-44 sm:px-[1.95rem] sm:py-[0.36rem] sm:text-[0.84rem] md:min-h-9 md:px-[2.1rem] md:py-[0.38rem] md:text-[0.95rem] lg:min-h-[2.1rem] lg:px-[2.2rem] lg:py-[0.4rem] lg:text-[0.8rem] xl:min-h-[1.8rem] xl:w-32 xl:px-[1.9rem] xl:text-[0.66rem] 2xl:min-h-[1.85rem] 2xl:px-[1.95rem] 2xl:text-[0.7rem]"
                            type="button"
                            dir="rtl"
                            x-show="($store.bp.is('base') && !wirdModeActive) || $store.bp.is('sm+')"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-x-0 translate-x-6"
                            x-transition:enter-end="opacity-100 scale-x-100 translate-x-0"
                            x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100 scale-x-100 translate-x-0"
                            x-transition:leave-end="opacity-0 scale-x-0 translate-x-6"
                            x-bind:disabled="wirdModeActive"
                            x-bind:class="{ 'quran-soorah-trigger--disabled': wirdModeActive }"
                            x-on:pointerdown="onSurahTriggerPointerDown($event)"
                            x-on:pointerup="onSurahTriggerPointerUp($event)"
                            x-on:pointercancel="onSurahTriggerPointerCancel()"
                            x-on:pointerleave="onSurahTriggerPointerCancel()"
                            x-on:click="onSurahTriggerClick()"
                            x-bind:aria-label="@js(arabic_text('ابحث في ')) + currentSurahTitle()"
                        >
                            <x-icon
                                class="quran-soorah-trigger-icon 4xl:size-4 3xl:size-4 4xl:inset-s-[0.82rem] inset-s-[0.62rem] sm:inset-s-[0.68rem] md:inset-s-[0.72rem] lg:inset-s-[0.76rem] xl:inset-s-[0.8rem] 2xl:inset-s-[0.85rem] 3xl:inset-s-[0.8rem] size-[0.8rem] sm:size-[0.84rem] md:size-[1.12rem] lg:size-[0.92rem] xl:size-[0.8rem] 2xl:size-3.5"
                                :name="'heroicon-o-magnifying-glass'"
                            />
                            <span class="quran-soorah-trigger-text">
                                <span
                                    class="quran-soorah-trigger-text-inner"
                                    x-bind:class="surahTriggerCaptionAnimClass"
                                    x-text="currentSurahTriggerLabel()"
                                ></span>
                            </span>
                            <span
                                class="quran-soorah-trigger-circle 4xl:size-4 size-[0.8rem] sm:size-[0.84rem] md:size-[0.88rem] lg:size-[0.92rem] xl:size-[0.96rem]"
                            ></span>
                        </button>
                        <div
                            class="quran-soorah-quick-nav inset-s-[48%] md:inset-s-[47%] lg:inset-s-[48%] 2xl:inset-s-[48%] 3xl:inset-s-[50%]"
                            x-cloak
                            x-bind:class="{ 'quran-soorah-quick-nav--visible': surahQuickNavigator.visible && !wirdModeActive }"
                            x-bind:aria-hidden="surahQuickNavigator.visible && !wirdModeActive ? 'false' : 'true'"
                        >
                            <button
                                class="quran-soorah-quick-nav-button quran-soorah-quick-nav-button--top inline-[2.1rem] md:inline-[2.3rem] lg:inline-[2.1rem] block-[1.72rem] md:block-[1.82rem] lg:block-[1.72rem] 3xl:inline-[2.1rem] 3xl:block-[1.72rem] 2xl:inline-8 2xl:block-[1.55rem] xl:block-[1.6rem] 3xl:inset-be-[calc(100%+1.4rem)] 3xl:transform-[translateX(-50%)] 4xl:inset-be-[calc(100%+1.5rem)] 4xl:transform-[translateX(-50%)] inset-be-[calc(100%+1.4rem)] transform-[translateX(-50%)] sm:inset-be-[calc(100%+1.4rem)] sm:transform-[translateX(-50%)] md:inset-be-[calc(100%+1.65rem)] md:transform-[translateX(-50%)] lg:inset-be-[calc(100%+1.4rem)] lg:transform-[translateX(-50%)] xl:inset-be-[calc(100%+1.3rem)] xl:transform-[translateX(-50%)] 2xl:inset-be-[calc(100%+1.3rem)] 2xl:transform-[translateX(-50%)]"
                                type="button"
                                aria-label="{{ arabic_text('السورة السابقة') }}"
                                x-bind:disabled="isSurahQuickNavigatorPreviousDisabled()"
                                x-on:click.stop.prevent="void navigateToAdjacentSurah('prev')"
                            >
                                <svg
                                    class="quran-soorah-quick-nav-arrow inline-[1.05rem] block-[1.05rem]"
                                    aria-hidden="true"
                                    viewBox="0 0 24 24"
                                >
                                    <path d="M12 5.25l-8.5 8.5 2.12 2.12L12 9.49l6.38 6.38 2.12-2.12L12 5.25z"></path>
                                </svg>
                            </button>
                            <button
                                class="quran-soorah-quick-nav-button quran-soorah-quick-nav-button--bottom inline-[2.1rem] md:inline-[2.3rem] lg:inline-[2.1rem] block-[1.72rem] md:block-[1.82rem] lg:block-[1.72rem] 3xl:inline-[2.1rem] 3xl:block-[1.72rem] 2xl:inline-8 2xl:block-[1.55rem] xl:block-[1.6rem] 3xl:inset-bs-[calc(100%+1.35rem)] 3xl:transform-[translateX(-50%)] 4xl:inset-bs-[calc(100%+1.45rem)] 4xl:transform-[translateX(-50%)] inset-bs-[calc(100%+1.35rem)] transform-[translateX(-50%)] sm:inset-bs-[calc(100%+1.35rem)] sm:transform-[translateX(-50%)] md:inset-bs-[calc(100%+1.65rem)] md:transform-[translateX(-50%)] lg:inset-bs-[calc(100%+1.35rem)] lg:transform-[translateX(-50%)] xl:inset-bs-[calc(100%+1.25rem)] xl:transform-[translateX(-50%)] 2xl:inset-bs-[calc(100%+1.35rem)] 2xl:transform-[translateX(-50%)]"
                                type="button"
                                aria-label="{{ arabic_text('السورة التالية') }}"
                                x-bind:disabled="isSurahQuickNavigatorNextDisabled()"
                                x-on:click.stop.prevent="void navigateToAdjacentSurah('next')"
                            >
                                <svg
                                    class="quran-soorah-quick-nav-arrow inline-[1.05rem] block-[1.05rem]"
                                    aria-hidden="true"
                                    viewBox="0 0 24 24"
                                >
                                    <path d="M12 18.75l8.5-8.5-2.12-2.12L12 14.51 5.62 8.13 3.5 10.25l8.5 8.5z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div
                        class="quran-top-actions gap-[0.2rem] sm:gap-[0.52rem]"
                        x-bind:class="{ 'quran-top-actions--wird-active': wirdModeActive }"
                    >
                        <!-- Credits: https://uiverse.io/vinodjangid07/tricky-bullfrog-41 -->
                        <button
                            class="quran-history-toggle-button quran-top-actions-secondary 4xl:basis-[2.35rem] 4xl:inline-[2.35rem] 4xl:block-[2.35rem] 4xl:max-w-[2.35rem] block-[1.68rem] inline-[1.68rem] sm:block-[2.05rem] sm:inline-[2.05rem] md:block-[2.32rem] md:inline-[2.42rem] lg:block-[2.2rem] lg:inline-[2.2rem] xl:block-[1.9rem] xl:inline-[2.27rem] 2xl:block-[2.1rem] 3xl:block-[2.3rem] 2xl:inline-[2.3rem] 3xl:basis-[2.3rem] max-w-[1.68rem] shrink-0 basis-[1.68rem] outline-none sm:max-w-[2.05rem] sm:basis-[2.05rem] md:max-w-[2.52rem] md:basis-[2.36rem] lg:max-w-[2.2rem] lg:basis-[2.2rem] xl:max-w-[1.9rem] xl:basis-10 2xl:max-w-[2.3rem] 2xl:basis-[2.15rem]"
                            id="quran-reader-history-toggle"
                            data-quran-open-history
                            type="button"
                            aria-label="{{ arabic_text('سجل التنقل') }}"
                            x-on:click="if (!wirdModeActive) { $wire.mountAction('navigationHistory') }"
                        >
                            <x-icon
                                class="quran-history-toggle-icon 4xl:size-6 3xl:size-[1.45rem] size-[1.2rem] sm:size-[1.26rem] md:h-[1.64rem] md:w-[1.64rem] lg:size-[1.4rem] xl:size-[1.3rem] 2xl:size-[1.3rem]"
                                :name="'heroicon-o-clock'"
                            />
                        </button>

                        <button
                            class="quran-support-lock-target quran-wird-progress-button 4xl:min-h-10 4xl:min-w-[min(13rem,50vw)] 3xl:min-h-[2.44rem] min-h-[1.85rem] min-w-[min(7rem,46vw)] outline-none sm:min-h-[2.2rem] sm:min-w-[min(11.4rem,50vw)] md:min-h-10 md:min-w-[min(11.9rem,50vw)] lg:min-h-[2.38rem] lg:min-w-[min(12.3rem,50vw)] xl:min-h-[2.1rem] xl:min-w-[min(10.7rem,50vw)] 2xl:min-h-[2.15rem] 2xl:min-w-[min(12.8rem,50vw)]"
                            data-quran-wird-toggle
                            data-support-lock-target="wird-progress"
                            type="button"
                            x-data="{ hovered: $useHover($el) }"
                            x-bind:style="wirdProgressBarStyle()"
                            x-bind:class="{
                                'quran-wird-progress-button--completed': ensureWirdDailyRecord()?.completed,
                                'quran-wird-progress-button--shimmer-running': wirdHoverShimmerRunning,
                                'quran-wird-progress-button--active-aura': wirdModeActive && !isSupportLockActive(),
                            }"
                            x-bind:aria-pressed="wirdModeActive ? 'true' : 'false'"
                            x-bind:aria-label="wirdModeActive ? @js(arabic_text('إيقاف وضع الوِرد والعودة للقراءة الحرة')) : @js(arabic_text('تشغيل وضع الوِرد اليومي'))"
                            x-on:click="toggleWirdMode()"
                            x-on:mouseenter="startWirdHoverEffects()"
                            x-on:mouseleave="endWirdHoverEffects()"
                            x-on:focusin="startWirdHoverEffects()"
                            x-on:focusout="endWirdHoverEffects()"
                        >
                            <span
                                class="quran-wird-progress-aura-water"
                                aria-hidden="true"
                            ></span>
                            <span
                                class="quran-wird-progress-aura-reflect"
                                aria-hidden="true"
                                x-show="doesEnableVisualEnhancements"
                            ></span>
                            <span
                                class="quran-wird-progress-aura-rainbow"
                                aria-hidden="true"
                                x-show="doesEnableVisualEnhancements && wirdModeActive && !isSupportLockActive()"
                            ></span>
                            <span
                                class="quran-wird-progress-hover-shimmer"
                                aria-hidden="true"
                            ></span>
                            <span
                                class="quran-wird-progress-fill quran-wird-progress-fill--committed"
                                aria-hidden="true"
                            ></span>
                            <span
                                class="quran-wird-progress-fill quran-wird-progress-fill--browse"
                                aria-hidden="true"
                            ></span>
                            <span class="quran-wird-progress-content">
                                <span
                                    class="quran-wird-progress-percent 4xl:min-h-[1.56rem] 4xl:min-w-[2.54rem] 4xl:px-[0.52rem] 4xl:text-[0.68rem] 2xl:min-h-5.5 3xl:min-h-6 min-h-[1.16rem] min-w-[1.8rem] px-[0.34rem] text-[0.52rem] sm:min-h-[1.4rem] sm:min-w-[2.22rem] sm:px-[0.46rem] sm:text-[0.61rem] md:min-h-[1.46rem] md:min-w-[2.32rem] md:px-[0.48rem] md:text-[0.75rem] lg:min-h-6 lg:min-w-[2.42rem] lg:px-2 lg:text-[0.66rem] xl:min-h-6 xl:text-[0.63rem] 2xl:text-[0.66rem]"
                                    x-text="wirdProgressPercentLabel()"
                                ></span>
                                <span
                                    class="text-primary-700 4xl:text-xs translate-y-1.5 text-[0.56rem] font-bold opacity-0 transition-all duration-500 sm:text-[0.68rem] md:text-[0.8rem] lg:text-[0.72rem] xl:text-[0.74rem]"
                                    x-bind:class="{
                                        'opacity-100! -translate-y-0.25!': ($store.bp.is('base') || (hovered ||
                                            wirdModeActive) && !isSupportLockActive()),
                                        'font-normal!': wirdModeActive,
                                    }"
                                >{{ arabic_text('الورد اليومي') }}</span>
                                <span
                                    class="quran-wird-progress-count 4xl:text-[0.78rem] 3xl:text-[0.75rem] hidden text-[0.62rem] sm:block sm:text-[0.7rem] md:text-[0.85rem] lg:text-[0.75rem] xl:text-[0.71rem] 2xl:text-[0.7rem]"
                                    x-text="wirdProgressCounterLabel()"
                                ></span>
                            </span>
                        </button>

                        <!-- Credits: https://uiverse.io/vinodjangid07/breezy-goose-71 -->
                        <button
                            class="quran-bookmark-toggle-button quran-top-actions-secondary 4xl:basis-[2.35rem] 4xl:inline-[2.35rem] 4xl:block-[2.35rem] 4xl:max-w-[2.35rem] block-[1.68rem] inline-[1.68rem] sm:block-[2.05rem] sm:inline-[2.05rem] md:block-[2.62rem] md:inline-[2.12rem] lg:block-[2.2rem] lg:inline-[2.2rem] xl:block-[1.97rem] xl:inline-8.5 2xl:block-[2.1rem] 3xl:block-[2.3rem] 2xl:inline-[2.3rem] 3xl:basis-[2.3rem] 3xl:rounded-[0.625rem] max-w-[1.68rem] shrink-0 basis-[1.68rem] rounded-lg outline-none sm:max-w-[2.05rem] sm:basis-[2.05rem] sm:rounded-[0.625rem] md:max-w-[2.62rem] md:basis-[2.52rem] md:rounded-[0.625rem] lg:max-w-[2.2rem] lg:basis-[2.2rem] lg:rounded-[0.525rem] xl:max-w-10 xl:basis-9 xl:rounded-lg 2xl:max-w-[2.3rem] 2xl:basis-[2.2rem] 2xl:rounded-[0.6rem]"
                            id="quran-reader-bookmark-toggle"
                            data-quran-bookmark-toggle
                            type="button"
                            x-bind:class="{ 'quran-bookmark-toggle-button--bookmarked': isCurrentPageBookmarked() }"
                            x-bind:aria-pressed="isCurrentPageBookmarked() ? 'true' : 'false'"
                            x-bind:aria-label="isCurrentPageBookmarked() ? @js(arabic_text('إزالة علامة الصفحة الحالية')) : @js(arabic_text('حفظ الصفحة الحالية كعلامة'))"
                            x-on:pointerdown="onBookmarkButtonPointerDown($event)"
                            x-on:pointerup="onBookmarkButtonPointerUp($event)"
                            x-on:pointercancel="onBookmarkButtonPointerCancel()"
                            x-on:pointerleave="onBookmarkButtonPointerCancel()"
                            x-on:click.prevent="if (!wirdModeActive) { onBookmarkButtonClick() }"
                        >
                            <span
                                class="quran-bookmark-toggle-fill"
                                aria-hidden="true"
                            ></span>
                            <svg
                                class="quran-bookmark-toggle-icon 4xl:w-3.75 3xl:w-[0.92rem] w-[0.68rem] sm:w-[0.82rem] md:w-4 lg:w-[0.89rem] xl:w-[0.8rem] 2xl:w-[0.8rem]"
                                aria-hidden="true"
                                width="15"
                                viewBox="0 0 50 70"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    d="M46 62.0085L46 3.88139L3.99609 3.88139L3.99609 62.0085L24.5 45.5L46 62.0085Z"
                                    stroke="#fff"
                                    stroke-width="7"
                                ></path>
                            </svg>
                        </button>
                    </div>
                </header>

                <div
                    class="3xl:my-1 4xl:my-3 3xl:px-0 4xl:px-4 Xoverflow-hidden relative my-2 min-h-0 flex-1 px-3 sm:my-3 sm:px-4 xl:my-1.5 xl:px-12 2xl:my-3 2xl:px-4"
                    x-ref="pageViewport"
                >
                    <div
                        class="quran-page-surface 3xl:pt-2.5 h-full rounded-2xl pt-2.5 transition-opacity duration-200 2xl:pt-1.5"
                        x-bind:class="pageMotionClass"
                        x-on:click="clearAyahSelectionOnBackground($event)"
                        x-ref="pageSurface"
                    >
                        @if ($qpcPageFontFamily !== null && $qpcPageFontUrl !== null && $qpcPageFontFormat !== null)
                            <style>
                                @font-face {
                                    font-family: '{{ $qpcPageFontFamily }}';
                                    src: url('{{ $qpcPageFontUrl }}') format('{{ $qpcPageFontFormat }}');
                                    font-display: block;
                                }
                            </style>
                        @endif

                        @if ($surahHeaderFontFamily !== null && $surahHeaderFontUrl !== null && $surahHeaderFontFormat !== null)
                            <style>
                                @font-face {
                                    font-family: '{{ $surahHeaderFontFamily }}';
                                    src: url('{{ $surahHeaderFontUrl }}') format('{{ $surahHeaderFontFormat }}');
                                    font-display: swap;
                                }
                            </style>
                        @endif

                        @if ($basmallahFontFamily !== null && $basmallahFontUrl !== null && $basmallahFontFormat !== null)
                            <style>
                                @font-face {
                                    font-family: '{{ $basmallahFontFamily }}';
                                    src: url('{{ $basmallahFontUrl }}') format('{{ $basmallahFontFormat }}');
                                    font-display: swap;
                                }
                            </style>
                        @endif

                        <div
                            class="Xoverflow-hidden mx-auto grid h-full w-fit max-w-full place-items-center items-center"
                            x-ref="pageFrame"
                        >
                            <div
                                class="quran-page-lines mx-auto pb-4"
                                x-bind:class="{
                                    'quran-page-lines--dense': isDenseFullLinePage(),
                                    'quran-page-lines--segmented': isMultiSurahSegmentedPage(),
                                    'quran-page-lines--headered-long': isSingleHeaderLongContentPage(),
                                    'quran-page-lines--opening': Number(pageNumber) <= 2,
                                }"
                                x-bind:data-fit-state="typeof pageFitState === 'function' ? pageFitState() : (isFittingPage ? 'fitting' :
                                    'ready')"
                                x-bind:style="pageContentStyle()"
                                x-on:click="clearAyahSelectionOnBackground($event)"
                                x-on:pointerup.window.passive="onWordPointerUp($event)"
                                x-on:pointercancel.window.passive="onWordPointerCancel()"
                                x-on:mouseleave="clearHoveredSegment()"
                                x-ref="pageContent"
                            >
                                <template
                                    x-for="line in mushafLines"
                                    :key="`quran-line-${pageNumber}-${line.line_number}-${line.line_type}`"
                                >
                                    <div
                                        data-quran-line
                                        x-data="{ lineEntry: line }"
                                        x-show="shouldRenderLine(lineEntry)"
                                        x-bind:class="lineAlignmentClass(lineEntry)"
                                        x-bind:data-quran-line-number="Number(lineEntry?.line_number ?? 0)"
                                        x-bind:data-quran-line-type="String(lineEntry?.line_type ?? '')"
                                        x-bind:style="lineEntryStyle(lineEntry)"
                                    >
                                        <template x-if="isBasmallahLine(lineEntry)">
                                            <div
                                                class="font-quran quran-basmallah-line"
                                                data-quran-line-text
                                                x-bind:style="basmallahLineStyle(lineEntry)"
                                            >
                                                <template x-if="isBasmallahLineWithWords(lineEntry)">
                                                    <template
                                                        x-for="(word, wordIndex) in lineEntry.words"
                                                        :key="`quran-basmallah-word-${pageNumber}-${lineEntry.line_number}-${word.word_index ?? wordIndex}`"
                                                    >
                                                        <span
                                                            class="quran-basmallah-word"
                                                            x-text="word.text"
                                                        ></span>
                                                    </template>
                                                </template>
                                                <template x-if="!isBasmallahLineWithWords(lineEntry)">
                                                    <span x-text="basmallahDisplayText(lineEntry)"></span>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!isBasmallahLine(lineEntry) && isAyahLineWithWords(lineEntry)">
                                            <div
                                                data-quran-line-text
                                                x-bind:class="ayahLineClass(lineEntry)"
                                                x-bind:style="lineFontStyle()"
                                            >
                                                <template
                                                    x-for="(cluster, clusterIndex) in lineWordClusters(lineEntry)"
                                                    :key="`quran-cluster-${pageNumber}-${lineEntry.line_number}-${cluster.key ?? clusterIndex}`"
                                                >
                                                    <span
                                                        class="quran-segment-cluster"
                                                        x-bind:class="{
                                                            'quran-segment-cluster-active': isAyahClusterActive(
                                                                cluster),
                                                            'quran-segment-cluster-search-highlighted': isAyahClusterSearchHighlighted(
                                                                cluster),
                                                            'quran-segment-cluster-hovered': isAyahClusterHovered(
                                                                cluster),
                                                            'quran-segment-cluster-copied': isAyahClusterCopied(
                                                                cluster),
                                                        }"
                                                    >
                                                        <template
                                                            x-for="(word, wordIndex) in cluster.words"
                                                            :key="`quran-word-${pageNumber}-${lineEntry.line_number}-${word.word_index ?? wordIndex}`"
                                                        >
                                                            <span class="quran-word-slot inline-flex items-baseline">
                                                                <button
                                                                    class="quran-word-button px-0 outline-none transition"
                                                                    data-quran-word-button
                                                                    type="button"
                                                                    tabindex="-1"
                                                                    x-bind:data-quran-ayah-index="Number(word?.ayah_index ?? 0)"
                                                                    x-bind:data-quran-word-index="Number(word?.word_index ?? 0)"
                                                                    x-bind:data-quran-ayah-number="Number(word?.ayah_number ?? 0)"
                                                                    x-bind:data-quran-surah-number="Number(word?.surah_number ?? lineEntry
                                                                        ?.surah_number ??
                                                                        0)"
                                                                    x-bind:class="{
                                                                        'quran-segment-active': isWordActive(word),
                                                                        'quran-segment-hovered': isWordHovered(word),
                                                                        'quran-segment-copied': isWordCopied(word),
                                                                    }"
                                                                    x-bind:disabled="!isSelectableWord(word)"
                                                                    x-on:pointerdown="onWordPointerDown($event, word)"
                                                                    x-on:pointermove="onWordPointerMove($event)"
                                                                    x-on:pointerup="onWordPointerUp($event)"
                                                                    x-on:pointercancel="onWordPointerCancel()"
                                                                    x-on:mouseleave="onWordPointerLeave(word)"
                                                                    x-on:click.stop="onWordClick($event, word)"
                                                                    x-on:mouseenter="setHoveredSegment(word)"
                                                                    x-on:focus="setHoveredSegment(word)"
                                                                    x-on:blur="clearHoveredSegment(word)"
                                                                    x-text="word.text"
                                                                ></button>
                                                                <template x-if="showAyahMarker(word)">
                                                                    <span
                                                                        class="quran-ayah-marker mr-0.5 text-[0.92rem]"
                                                                        style="color: var(--quran-subtle);"
                                                                        x-text="'۝' + word.ayah_number"
                                                                    ></span>
                                                                </template>
                                                            </span>
                                                        </template>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                        <template
                                            x-if="!isBasmallahLine(lineEntry) && !isAyahLineWithWords(lineEntry)">
                                            <template x-if="isSurahHeaderLine(lineEntry)">
                                                <div
                                                    class="quran-surah-header-line"
                                                    data-quran-line-text
                                                    x-bind:class="{
                                                        'quran-surah-header-line--fatiha': Number(lineEntry
                                                            ?.surah_number ??
                                                            0) === 1
                                                    }"
                                                    x-bind:style="surahHeaderLineStyle(lineEntry)"
                                                >
                                                    <span
                                                        class="quran-surah-header-glyph"
                                                        x-text="surahHeaderLineText(lineEntry)"
                                                    ></span>
                                                </div>
                                            </template>
                                            <template x-if="!isSurahHeaderLine(lineEntry)">
                                                <div
                                                    class="font-quran quran-meta-line"
                                                    data-quran-line-text
                                                    x-bind:style="metaLineStyle(lineEntry)"
                                                    x-text="lineText(lineEntry)"
                                                ></div>
                                            </template>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <footer
                    class="quran-bottom-strip gap-x-[0.65rem] gap-y-[0.175rem] sm:gap-y-[0.42rem] md:gap-y-2 lg:gap-y-[0.2rem] xl:gap-x-[0.65rem] xl:gap-y-[0.24rem] 2xl:gap-x-[0.65rem] 2xl:gap-y-[0.42rem]"
                >
                    <button
                        class="quran-swipe-hint quran-swipe-hint-button quran-bottom-strip-nav-prev 4xl:min-h-[2.2rem] 4xl:min-w-[4.2rem] 4xl:px-1 4xl:py-[0.1rem] min-h-[1.95rem] min-w-[3.45rem] select-none px-[0.18rem] py-[0.06rem] outline-none sm:min-h-8 sm:min-w-[3.65rem] sm:px-[0.2rem] sm:py-[0.08rem] md:min-h-[2.05rem] md:min-w-[3.8rem] md:px-[0.22rem] md:py-[0.08rem] lg:min-h-[2.1rem] lg:min-w-[3.95rem] lg:px-[0.24rem] xl:min-w-[4.05rem]"
                        type="button"
                        aria-label="{{ arabic_text('الصفحة السابقة') }}"
                        x-ref="prevChevronButton"
                        x-on:click.stop.prevent="goPreviousFromChevron()"
                    >
                        <span
                            class="quran-swipe-hint-chev 4xl:text-[2rem] text-[1.5rem] sm:text-[2rem] md:text-[2.5rem] lg:text-[1.84rem] xl:text-[1.8rem] 2xl:text-[1.92rem]"
                            x-bind:class="{ 'quran-swipe-hint-chev-static': isFirstNavigationPage() }"
                        >‹</span>
                        <span
                            class="quran-swipe-hint-chev 4xl:text-[2rem] text-[1.5rem] sm:text-[2rem] md:text-[2.5rem] lg:text-[1.84rem] xl:text-[1.8rem] 2xl:text-[1.92rem]"
                            x-bind:class="{ 'quran-swipe-hint-chev-static': isFirstNavigationPage() }"
                        >‹</span>
                        <span
                            class="quran-swipe-hint-chev 4xl:text-[2rem] text-[1.5rem] sm:text-[2rem] md:text-[2.5rem] lg:text-[1.84rem] xl:text-[1.8rem] 2xl:text-[1.92rem]"
                            x-bind:class="{ 'quran-swipe-hint-chev-static': isFirstNavigationPage() }"
                        >‹</span>
                    </button>
                    <div class="quran-bottom-strip-center">
                        <div
                            class="quran-page-counter 4xl:min-h-[2.4rem] min-h-8 sm:min-h-[2.1rem] md:min-h-[2.2rem] lg:min-h-[2.28rem]"
                            x-bind:class="{ 'quran-page-counter--morphing': pageCounterPulse.isActive && pageCounterPulse.hasChanges }"
                        >
                            <button
                                class="quran-page-slider-chip 3xl:min-w-[5.8rem] 4xl:min-w-[5.8rem] 4xl:px-[0.56rem] 4xl:py-[0.28rem] 4xl:text-[0.84rem] 3xl:px-[0.52rem] 3xl:py-[0.26rem] 3xl:text-[0.82rem] sm:min-w-21 min-w-[4.8rem] select-none rounded-full px-[0.42rem] py-[0.2rem] text-[0.69rem] outline-none sm:px-2 sm:pb-[0.24rem] sm:pt-[0.16rem] sm:text-[0.85rem] md:min-w-[5.2rem] md:px-3 md:py-[0.3rem] md:text-[1rem] lg:min-w-[5.4rem] lg:px-[0.52rem] lg:py-[0.26rem] lg:text-[0.77rem] xl:min-w-20 xl:rounded-lg xl:text-[0.68rem] 2xl:min-w-[4.4rem] 2xl:rounded-full 2xl:px-[0.52rem] 2xl:py-[0.26rem] 2xl:text-[0.7rem]"
                                type="button"
                                x-bind:aria-label="wirdModeActive ? @js(arabic_text('وضع الوِرد اليومي مفعل')) : @js(arabic_text('إدخال رقم صفحة'))"
                                x-bind:style="`--quran-counter-digit-count: ${pageCounterDigitLength()};`"
                                x-bind:disabled="wirdModeActive"
                                x-bind:class="{ 'quran-page-slider-chip--disabled': wirdModeActive }"
                                x-on:click="if (!wirdModeActive) { $wire.mountAction('jumpToPage') }"
                            >
                                <span class="quran-page-chip-total me-1.5">
                                    <template
                                        x-for="(digit, digitIndex) in pageCounterDisplayDigits(pageCounterMaxDisplayValue())"
                                        :key="`quran-page-max-digit-${digitIndex}`"
                                    >
                                        <span class="quran-counter-cell">
                                            <span
                                                class="quran-counter-static"
                                                x-text="digit"
                                            ></span>
                                        </span>
                                    </template>
                                </span>
                                <span class="quran-page-chip-separator me-1.5">/</span>
                                <span class="quran-page-chip-current-wrap">
                                    <span class="quran-page-chip-current">
                                        <template
                                            x-for="(digit, digitIndex) in pageCounterDisplayDigits(pageCounterCurrentDisplayValue())"
                                            :key="`quran-page-current-digit-${digitIndex}`"
                                        >
                                            <span class="quran-counter-cell">
                                                <span
                                                    class="quran-counter-static"
                                                    x-text="digit"
                                                ></span>
                                            </span>
                                        </template>
                                    </span>
                                    <span
                                        class="quran-page-counter-morph"
                                        aria-hidden="true"
                                        x-cloak
                                        x-show="pageCounterPulse.isActive && pageCounterPulse.hasChanges"
                                    >
                                        <template
                                            x-for="segment in pageCounterPulse.segments"
                                            :key="segment.key"
                                        >
                                            <span class="quran-counter-cell">
                                                <span
                                                    x-show="!segment.changed"
                                                    x-text="segment.next"
                                                ></span>
                                                <span
                                                    class="quran-counter-roll"
                                                    x-show="segment.changed"
                                                >
                                                    <span
                                                        class="quran-counter-roll__prev"
                                                        x-text="segment.prev"
                                                    ></span>
                                                    <span
                                                        class="quran-counter-roll__next"
                                                        x-text="segment.next"
                                                    ></span>
                                                </span>
                                            </span>
                                        </template>
                                    </span>
                                </span>
                            </button>
                            <span
                                class="border-primary-300/55 bg-primary-50/75 text-primary-800 4xl:ms-1.5 4xl:min-h-[2.06rem] 4xl:rounded-[0.72rem] 4xl:px-2.5 4xl:text-[0.72rem] ms-1 inline-flex min-h-[1.72rem] items-center gap-1 rounded-[0.58rem] border px-2 text-[0.62rem] font-semibold sm:min-h-[1.78rem] sm:text-[0.64rem] md:min-h-[1.84rem] md:text-[0.66rem] lg:min-h-[1.9rem] lg:text-[0.68rem]"
                                data-quran-mushaf-page-indicator
                                x-cloak
                                x-show="shouldShowMushafPageIndicator()"
                                x-transition.opacity.duration.180ms
                                x-bind:aria-label="`${@js(arabic_text('رقم صفحة المصحف الحالية'))}: ${formatReaderNumber(currentMushafPageDisplayValue())}`"
                            >
                                <span>{{ arabic_text('صفحة') }}</span>
                                <span x-text="formatReaderNumber(currentMushafPageDisplayValue())"></span>
                            </span>
                        </div>
                    </div>
                    <div class="quran-bottom-strip-slider">
                        <input
                            class="quran-page-slider w-[min(42vw, 13.2rem)] 2xl:w-[min(42vw, 13.2rem)] 3xl:w-[min(42vw, 13.2rem)] 3xl:min-w-48 4xl:min-w-54 xl:min-w-42 lg:min-w-45 md:min-w-55 h-[0.56rem] min-w-32 outline-none sm:h-[0.56rem] sm:min-w-40 md:h-[0.7rem] lg:h-[0.56rem] xl:h-[0.46rem] 2xl:h-[0.56rem] 2xl:min-w-40"
                            type="range"
                            aria-label="{{ arabic_text('التنقل بين صفحات المصحف') }}"
                            x-bind:min="sliderMin()"
                            x-bind:max="sliderMax()"
                            x-bind:step="1"
                            x-bind:value="sliderValue()"
                            x-on:input="onSliderInput($event)"
                            x-on:change="onSliderCommit($event)"
                            x-on:pointerup="onSliderPointerRelease($event)"
                            x-on:mouseup="onSliderPointerRelease($event)"
                            x-on:touchend="onSliderPointerRelease($event)"
                            x-on:pointercancel="onSliderPointerRelease($event)"
                        />
                    </div>
                    <button
                        class="quran-swipe-hint quran-swipe-hint-button quran-bottom-strip-nav-next 4xl:min-h-[2.2rem] 4xl:min-w-[4.2rem] 4xl:px-1 4xl:py-[0.1rem] min-h-[1.95rem] min-w-[3.45rem] select-none px-[0.18rem] py-[0.06rem] outline-none sm:min-h-8 sm:min-w-[3.65rem] sm:px-[0.2rem] sm:py-[0.08rem] md:min-h-[2.05rem] md:min-w-[3.8rem] md:px-[0.22rem] md:py-[0.08rem] lg:min-h-[2.1rem] lg:min-w-[3.95rem] lg:px-[0.24rem] xl:min-w-[4.05rem]"
                        type="button"
                        aria-label="{{ arabic_text('الصفحة التالية') }}"
                        x-ref="nextChevronButton"
                        x-bind:disabled="!wirdModeActive && isLastNavigationPage()"
                        x-on:click.stop.prevent="goNextFromChevron()"
                    >
                        <span
                            class="quran-swipe-hint-chev-opposite quran-swipe-hint-chev 4xl:text-[2rem] text-[1.5rem] sm:text-[2rem] md:text-[2.5rem] lg:text-[1.84rem] xl:text-[1.8rem] 2xl:text-[1.92rem]"
                            x-bind:class="{ 'quran-swipe-hint-chev-static': !wirdModeActive && isLastNavigationPage() }"
                        >›</span>
                        <span
                            class="quran-swipe-hint-chev-opposite quran-swipe-hint-chev 4xl:text-[2rem] text-[1.5rem] sm:text-[2rem] md:text-[2.5rem] lg:text-[1.84rem] xl:text-[1.8rem] 2xl:text-[1.92rem]"
                            x-bind:class="{ 'quran-swipe-hint-chev-static': !wirdModeActive && isLastNavigationPage() }"
                        >›</span>
                        <span
                            class="quran-swipe-hint-chev-opposite quran-swipe-hint-chev 4xl:text-[2rem] text-[1.5rem] sm:text-[2rem] md:text-[2.5rem] lg:text-[1.84rem] xl:text-[1.8rem] 2xl:text-[1.92rem]"
                            x-bind:class="{ 'quran-swipe-hint-chev-static': !wirdModeActive && isLastNavigationPage() }"
                        >›</span>
                    </button>
                </footer>

                <template x-teleport="body">
                    <div
                        class="quran-copy-popover"
                        data-quran-copy-popover
                        x-cloak
                        x-show="copyFeedback.visible"
                        x-bind:style="copyFeedbackStyle()"
                        x-transition:enter="quran-copy-popover-enter"
                        x-transition:enter-start="quran-copy-popover-enter-start"
                        x-transition:enter-end="quran-copy-popover-enter-end"
                        x-transition:leave="quran-copy-popover-leave"
                        x-transition:leave-start="quran-copy-popover-leave-start"
                        x-transition:leave-end="quran-copy-popover-leave-end"
                    >
                        <x-icon
                            class="h-3.5 w-3.5"
                            :name="'heroicon-o-clipboard'"
                        />
                        <span>{{ arabic_text('تم النسخ') }}</span>
                    </div>
                </template>

                <x-partials.shared.congrats-overlay
                    show="isWirdCompletionVisible || isWirdCompletionPreviewPinned"
                    :title="arabic_text('هنيئًا لك إتمام الوِرد اليومي')"
                    :subtitle="arabic_text('ثبَّتَ الله لك الأجر، وتم حفظ تقدّمك لليوم.')"
                    transition-enter="transition-opacity ease-out duration-200"
                    transition-enter-start="opacity-0"
                    transition-enter-end="opacity-100"
                    transition-leave="transition-opacity ease-in duration-200"
                    transition-leave-start="opacity-100"
                    transition-leave-end="opacity-0"
                    decorated
                    arabesque
                    pattern-variant="connected"
                    with-backdrop
                    backdrop-class="bg-white/98"
                    fixed-layer
                    max-width-class="max-w-4xl py-12"
                />
            </section>
        @endif
    </div>

    <x-filament-actions::modals />
</div>
