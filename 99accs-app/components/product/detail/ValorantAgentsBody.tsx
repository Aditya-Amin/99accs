'use client';
import { useState } from 'react';
import ProductGallerySingle from './ProductGallerySingle';
import ProductDetailContent from './ProductDetailContent';
import ProductProfileInfo from './ProductProfileInfo';
import ValorantSkinInventory from './ValorantSkinInventory';
import ValorantBuddyInventory from './ValorantBuddyInventory';
import type { Product, ProductAgentEntry } from '@/lib/api/types';

interface ValorantAgentsBodyProps {
  product: Product;
}

export default function ValorantAgentsBody({ product }: ValorantAgentsBodyProps) {
  const agents: ProductAgentEntry[] = product.agents_detailed ?? [];
  const [activeId, setActiveId] = useState(agents[0]?.id ?? '');
  const activeIndex = Math.max(0, agents.findIndex((a) => a.id === activeId));
  const previewImg = activeIndex % 2 === 0 ? '/img/images/agent_nav_img01.png' : '/img/images/agent_nav_img02.png';
  const agentsCount = product.agents_count ?? agents.length;

  return (
    <section className="shop__details-area section-pb-130">
      <div className="container">
        <div className="shop__details-wrap">
          <ProductGallerySingle
            image={product.images[0] ?? '/img/valorant/exclusive_img_01.jpg'}
            alt={product.title}
          />
          <ProductDetailContent product={product} variant="rich" showCountry />
        </div>

        {product.profile_info && <ProductProfileInfo info={product.profile_info} />}

        <div className="shop__details-inventory">
          <div className="shop__details-agent">
            <h2 className="inventory-title"><span>{agentsCount}</span>Agents</h2>
            <div className="shop__details-agent-wrap">
              <div className="shop__details-agent-nav">
                <ul className="list-wrap">
                  {agents.map((agent) => (
                    <li
                      key={agent.id}
                      className={agent.id === activeId ? 'active' : undefined}
                      onClick={() => setActiveId(agent.id)}
                    >
                      <img src={agent.image} alt="img" className="tab-img" />
                      {agent.role_icon && (
                        <img src={agent.role_icon} alt="icon" className="role-icon" />
                      )}
                    </li>
                  ))}
                </ul>
              </div>
              <div className="shop__details-agent-tav">
                <div className="tab-pane active">
                  <div className="img-wrap">
                    <img src="/img/images/agent_nav_bg01.jpg" alt="img" className="bg_img" />
                    <img src="/img/images/agent_nav_bg_shape01.png" alt="img" className="shape-one" />
                    <img src="/img/images/agent_nav_bg_shape02.png" alt="img" className="shape-two" />
                  </div>
                  <img src={previewImg} alt="img" className="main_img" />
                </div>
              </div>
            </div>
          </div>

          {product.skin_inventory && product.skin_filters && (
            <ValorantSkinInventory inventory={product.skin_inventory} filters={product.skin_filters} />
          )}
          {product.buddy_inventory && (
            <ValorantBuddyInventory inventory={product.buddy_inventory} />
          )}
        </div>

        {product.description && (
          <div className="shop__description-wrap">
            <div dangerouslySetInnerHTML={{ __html: product.description }} />
          </div>
        )}
      </div>
    </section>
  );
}
