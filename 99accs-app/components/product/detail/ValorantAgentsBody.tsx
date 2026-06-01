'use client';
import { useState } from 'react';
import ProductGallerySingle from './ProductGallerySingle';
import ProductDetailContent from './ProductDetailContent';
import ProductProfileInfo from './ProductProfileInfo';
import ValorantSkinInventory from './ValorantSkinInventory';
import ValorantBuddyInventory from './ValorantBuddyInventory';
import DescriptionSections from './DescriptionSections';
import type { Product, ProductAgentEntry } from '@/lib/api/types';

interface ValorantAgentsBodyProps {
  product: Product;
}

// Generic Valorant placeholder used when a product has no images uploaded yet.
// Keeps the side-by-side thumbnail/info layout intact instead of collapsing to
// a full-width content column.
const PRODUCT_PLACEHOLDER = '/img/valorant/skin_img_01.png';

export default function ValorantAgentsBody({ product }: ValorantAgentsBodyProps) {
  const agents: ProductAgentEntry[] = product.agents_detailed ?? [];
  const [activeId, setActiveId] = useState(agents[0]?.id ?? '');
  const activeIndex = Math.max(0, agents.findIndex((a) => a.id === activeId));
  const previewImg = agents[activeIndex]?.image ?? null;
  const agentsCount = product.agents_count ?? agents.length;
  const hasAgents = agents.length > 0;
  const hasSkinInventory = !!(product.skin_inventory && product.skin_filters);
  const hasBuddyInventory = !!product.buddy_inventory;
  const hasInventory = hasAgents || hasSkinInventory || hasBuddyInventory;

  return (
    <section className="shop__details-area section-pb-130">
      <div className="container">
        <div className="shop__details-wrap">
          <ProductGallerySingle
            image={product.images[0] ?? PRODUCT_PLACEHOLDER}
            alt={product.title}
          />
          <ProductDetailContent product={product} variant="rich" showCountry />
        </div>

        {product.profile_info && <ProductProfileInfo info={product.profile_info} />}

        {hasInventory && (
        <div className="shop__details-inventory">
          {hasAgents && (
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
                    {previewImg && <img src={previewImg} alt="img" className="main_img" />}
                  </div>
                </div>
              </div>
            </div>
          )}

          {product.skin_inventory && product.skin_filters && (
            <ValorantSkinInventory inventory={product.skin_inventory} filters={product.skin_filters} />
          )}
          {product.buddy_inventory && (
            <ValorantBuddyInventory inventory={product.buddy_inventory} />
          )}
        </div>
        )}

        <DescriptionSections
          sections={product.description_sections ?? []}
          faqItems={product.faq_items}
          htmlFallback={product.description}
        />
      </div>
    </section>
  );
}
