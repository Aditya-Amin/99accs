import { IconShieldCheck, IconTrophy, IconDelivery, IconWarranty } from '@/components/icons';
import type { BannerIcon, BannerSubtitle } from '@/lib/api/types';

const ICON_MAP: Record<BannerIcon, React.ComponentType> = {
  shield_check: IconShieldCheck,
  trophy: IconTrophy,
  delivery: IconDelivery,
  warranty: IconWarranty,
};

export function BannerIconByName({ name }: { name: BannerIcon }) {
  const Icon = ICON_MAP[name] ?? IconShieldCheck;
  return <Icon />;
}

// --- Sub-title badge ---
export function BannerSubTitle({ subtitle }: { subtitle: BannerSubtitle }) {
  return (
    <span className="sub-title wow fadeInUp" data-wow-delay=".2s">
      <BannerIconByName name={subtitle.icon} />
      {subtitle.text}
    </span>
  );
}

// --- Main heading + description paragraph ---
export function BannerTitle({ heading, description }: { heading: string; description: string }) {
  return (
    <>
      {/* heading is sanitized HTML (server strips all but span/strong/em/br/mark) */}
      <h2
        className="title wow fadeInUp"
        data-wow-delay=".4s"
        dangerouslySetInnerHTML={{ __html: heading }}
      />
      <p className="wow fadeInUp" data-wow-delay=".5s">
        {description}
      </p>
    </>
  );
}

// --- Text block: sub-title + heading + description ---
interface BannerContentProps {
  subtitle: BannerSubtitle;
  heading: string;
  description: string;
}
export function BannerContent({ subtitle, heading, description }: BannerContentProps) {
  return (
    <div className="banner__content text-center">
      <BannerSubTitle subtitle={subtitle} />
      <BannerTitle heading={heading} description={description} />
    </div>
  );
}
