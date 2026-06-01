'use client';
import { useState } from 'react';
import ProductDetailContent from './ProductDetailContent';
import { FortniteStatIcon } from './FortniteAccountStatIcons';
import { SeasonStatIcon } from './FortniteSeasonIcons';
import { CurrentSeasonStatIcon } from './FortniteCurrentSeasonIcons';
import DescriptionSections from './DescriptionSections';
import type { Product, LockerTabKey } from '@/lib/api/types';

interface FortniteLockerBodyProps {
  product: Product;
}

type MainTab = 'tab1' | 'tab2' | 'tab3' | 'tab4';

export default function FortniteLockerBody({ product }: FortniteLockerBodyProps) {
  const [mainTab, setMainTab] = useState<MainTab>('tab1');
  const firstLockerKey: LockerTabKey = product.locker?.tabs[0]?.key ?? 'all';
  const [lockerTab, setLockerTab] = useState<LockerTabKey>(firstLockerKey);

  const hasAccount = !!product.account_level || !!product.account_stats?.length;
  const hasLocker = !!product.locker?.tabs.length;
  const hasSeasonsCurrent = !!product.seasons?.current;
  const hasSeasonsHistory = !!product.seasons?.history?.length;

  const activeLocker = product.locker?.tabs.find((t) => t.key === lockerTab) ?? product.locker?.tabs[0];

  return (
    <section className="shop__details-area section-pb-130">
      <div className="container">
        <div className="shop__details-wrap shop__details-wrap-two">
          <div className="shop__details-thumb shop__details-thumb-four">
            {product.images[0] && <img src={product.images[0]} alt={product.title} />}
          </div>
          <ProductDetailContent product={product} variant="fortnite_four" showCountry={false} />
        </div>

        <div className="shop__details-nav">
          {hasAccount && (
            <button className={mainTab === 'tab1' ? 'active' : ''} onClick={() => setMainTab('tab1')}>
              Account
            </button>
          )}
          {hasLocker && (
            <button className={mainTab === 'tab2' ? 'active' : ''} onClick={() => setMainTab('tab2')}>
              Locker
            </button>
          )}
          {hasSeasonsCurrent && (
            <button className={mainTab === 'tab3' ? 'active' : ''} onClick={() => setMainTab('tab3')}>
              Seasons
            </button>
          )}
          {hasSeasonsHistory && (
            <button className={mainTab === 'tab4' ? 'active' : ''} onClick={() => setMainTab('tab4')}>
              PVE
            </button>
          )}
        </div>

        <div className="shop__details-tab">
          {mainTab === 'tab1' && hasAccount && (
            <div className="tab-pane account-wrap active">
              {product.account_level && (
                <div className="account__top">
                  <img src="/img/fortnite/gun_01.svg" alt="shape" className="shape" />
                  <div className="account__top-content">
                    <div className="account__badge">
                      <img src="/img/fortnite/polygon_01.png" alt="shape" />
                      <span className="title"><strong>{product.account_level.value}</strong>lvl</span>
                    </div>
                    <div className="content">
                      <h2 className="title">{product.account_level.label ?? 'Account Level'}</h2>
                      {product.account_level.description && <p>{product.account_level.description}</p>}
                    </div>
                  </div>
                  <img src="/img/fortnite/gun_02.svg" alt="shape" className="shape" />
                </div>
              )}
              <div className="account__item-wrap">
                {product.account_stats?.map((stat, i) => (
                  <div key={i} className="account__item">
                    <div className="icon">
                      <img src="/img/fortnite/polygon.svg" alt="shape" />
                      {stat.icon && <FortniteStatIcon icon={stat.icon} />}
                    </div>
                    <div className="content">
                      <h2 className="title">{stat.value}</h2>
                      <span>{stat.label}</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {mainTab === 'tab2' && hasLocker && product.locker && (
            <div className="tab-pane locker-wrap active">
              <div className="inner-tab-wrapper">
                <div className="locker-nav">
                  {product.locker.tabs.map((tab) => (
                    <button
                      key={tab.key}
                      className={tab.key === lockerTab ? 'active' : ''}
                      onClick={() => setLockerTab(tab.key)}
                    >
                      {tab.label}
                    </button>
                  ))}
                </div>
                <div className="divider"></div>
                <div className="locker-tab-wrap">
                  {activeLocker && (
                    <div className="locker-tab active">
                      {activeLocker.groups.map((grp, i) => (
                        <div key={i}>
                          <div className="inventory-title-wrap">
                            {typeof grp.count === 'number' && (
                              <h2 className="inventory-title"><span>{grp.count}</span>{grp.title}</h2>
                            )}
                            {typeof grp.purchased === 'number' && (
                              <h2 className="inventory-title"><span>{grp.purchased}</span>Purchased</h2>
                            )}
                            {typeof grp.vbucks === 'number' && (
                              <h2 className="inventory-title"><span>{grp.vbucks}</span>V-Bucks</h2>
                            )}
                          </div>
                          <div className="buddies__item-wrap">
                            {grp.items.map((item) => (
                              <div key={item.id} className="buddies__item">
                                <div className="thumb">
                                  <img src={item.image} alt="img" />
                                </div>
                                <div className="content">
                                  <span className="title">{item.name}</span>
                                </div>
                              </div>
                            ))}
                          </div>
                          {i < activeLocker.groups.length - 1 && <div className="divider"></div>}
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}

          {mainTab === 'tab3' && hasSeasonsCurrent && product.seasons?.current && (
            <div className="tab-pane season-wrap active">
              <h2 className="season-title">Current Season</h2>
              <div className="season-badge">
                <div className="icon">
                  <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M17.627 1.2998C19.0354 0.233745 20.9646 0.233744 22.373 1.2998L37.8965 13.0488C39.2557 14.0776 39.8274 15.8728 39.3135 17.5205L33.3389 36.6777C32.8134 38.3624 31.277 39.4999 29.5488 39.5H10.4512C8.72302 39.4999 7.18657 38.3624 6.66113 36.6777L0.686523 17.5205C0.172608 15.8728 0.744348 14.0776 2.10352 13.0488L17.627 1.2998Z" stroke="currentColor" />
                  </svg>
                  <span className="number">{product.seasons.current.number}</span>
                </div>
                <h2 className="title">Season</h2>
              </div>
              <ul className="list-wrap season-list">
                {product.seasons.current.stats.map((s, i) => (
                  <li key={i}>
                    {s.icon && (
                      <div className="icon">
                        <CurrentSeasonStatIcon icon={s.icon} />
                      </div>
                    )}
                    <p>{s.label}: <span>{s.value}</span></p>
                  </li>
                ))}
              </ul>
            </div>
          )}

          {mainTab === 'tab4' && hasSeasonsHistory && product.seasons?.history && (
            <div className="tab-pane active">
              <h2 className="season-title">Seasons History</h2>
              {product.seasons.chapter_title && (
                <h2 className="chapter-title">{product.seasons.chapter_title}</h2>
              )}
              <div className="row gutter-y-24 row-cols-1 row-cols-xl-5 row-cols-lg-4 row-cols-md-3 row-cols-sm-2">
                {product.seasons.history.map((s) => {
                  const bg = product.seasons?.history_background;
                  return (
                    <div className="col" key={s.season}>
                      <div
                        className="season-item"
                        data-background={bg ?? undefined}
                        style={bg ? { backgroundImage: `url(${bg})` } : undefined}
                      >
                        <div className="icon">
                          <img src="/img/fortnite/polygon_02.svg" alt="shape" />
                          <h2 className="title">{s.season}<span>Season</span></h2>
                        </div>
                        <ul className="list-wrap">
                          <li>
                            <div className="icon"><SeasonStatIcon icon="level" /></div>
                            <span>Level: {s.level}</span>
                          </li>
                          <li>
                            <div className="icon"><SeasonStatIcon icon="season_wins" /></div>
                            <span>Season Wins: {s.season_wins}</span>
                          </li>
                          <li>
                            <div className="icon"><SeasonStatIcon icon="bp_level" /></div>
                            <span>BP Level: {s.bp_level}</span>
                          </li>
                          <li>
                            <div className="icon"><SeasonStatIcon icon="bp_purchased" /></div>
                            <span>BP Purchased: {s.bp_purchased}</span>
                          </li>
                        </ul>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}
        </div>

        <DescriptionSections
          sections={product.description_sections ?? []}
          faqItems={product.faq_items}
          htmlFallback={product.description}
        />
      </div>
    </section>
  );
}
