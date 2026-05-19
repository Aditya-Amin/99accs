import type { ProductProfileInfo as ProfileInfo, ProfileFeatureIcon } from '@/lib/api/types';

interface ProductProfileInfoProps {
  info: ProfileInfo;
}

function FeatureIcon({ icon }: { icon: ProfileFeatureIcon }) {
  if (icon === 'mail') {
    return (
      <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M17.9655 4.15649L11.652 10.47C10.948 11.1722 9.99431 11.5665 9 11.5665C8.00569 11.5665 7.05197 11.1722 6.348 10.47L0.0345 4.15649C0.024 4.27499 0 4.38224 0 4.49999V13.5C0.00119089 14.4942 0.396661 15.4473 1.09966 16.1503C1.80267 16.8533 2.7558 17.2488 3.75 17.25H14.25C15.2442 17.2488 16.1973 16.8533 16.9003 16.1503C17.6033 15.4473 17.9988 14.4942 18 13.5V4.49999C18 4.38224 17.976 4.27499 17.9655 4.15649Z" fill="currentColor" />
        <path d="M10.5921 9.4095L17.4426 2.55825C17.1107 2.00799 16.6427 1.55253 16.0836 1.2358C15.5245 0.919067 14.8932 0.751755 14.2506 0.75H3.75059C3.10801 0.751755 2.47672 0.919067 1.91761 1.2358C1.35851 1.55253 0.890453 2.00799 0.558594 2.55825L7.40909 9.4095C7.83177 9.83049 8.40403 10.0669 9.00059 10.0669C9.59715 10.0669 10.1694 9.83049 10.5921 9.4095Z" fill="currentColor" />
      </svg>
    );
  }
  if (icon === 'clock') {
    return (
      <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M15.7795 3.339L16.4395 3.999L17.5 2.9385L15.1097 0.5475L14.0485 1.60875L14.7175 2.277L13.9315 3.15C12.7148 2.23472 11.2671 1.6766 9.75095 1.53825V0H8.25095V1.53825C6.73476 1.6766 5.28711 2.23472 4.07045 3.15L3.28445 2.277L3.99995 1.5615L2.93945 0.501L0.501953 2.9385L1.56245 3.999L2.22245 3.339L2.95595 4.15425C1.8632 5.33084 1.13755 6.80075 0.868012 8.38372C0.598471 9.9667 0.796748 11.5939 1.43853 13.0659C2.08031 14.5378 3.13769 15.7904 4.48101 16.6702C5.82433 17.55 7.39519 18.0186 9.00095 18.0186C10.6067 18.0186 12.1776 17.55 13.5209 16.6702C14.8642 15.7904 15.9216 14.5378 16.5634 13.0659C17.2052 11.5939 17.4034 9.9667 17.1339 8.38372C16.8644 6.80075 16.1387 5.33084 15.046 4.15425L15.7795 3.339ZM9.00095 11.25C8.67103 11.2511 8.35 11.1431 8.08786 10.9428C7.82572 10.7425 7.63717 10.4611 7.55159 10.1425C7.466 9.82382 7.48819 9.48584 7.61468 9.18113C7.74117 8.87642 7.96488 8.62209 8.25095 8.45775V4.5H9.75095V8.45775C10.037 8.62209 10.2607 8.87642 10.3872 9.18113C10.5137 9.48584 10.5359 9.82382 10.4503 10.1425C10.3647 10.4611 10.1762 10.7425 9.91405 10.9428C9.6519 11.1431 9.33087 11.2511 9.00095 11.25Z" fill="currentColor" />
      </svg>
    );
  }
  return (
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M12.3752 17.91C13.5827 17.91 14.7002 17.4525 15.5327 16.62L17.9102 14.2425L13.2152 9.54L10.6577 12.0975C8.43766 11.1375 6.84766 9.54 5.82016 7.245L8.37766 4.6875L3.66766 0L1.29016 2.3775C0.457657 3.2025 0.000156403 4.3275 0.000156403 5.535C0.000156403 10.9725 6.93766 17.91 12.3752 17.91Z" fill="currentColor" />
    </svg>
  );
}

export default function ProductProfileInfo({ info }: ProductProfileInfoProps) {
  const hasTop = info.region || info.profile_image || info.inventory_value;
  return (
    <div className="profile__info-wrap">
      {hasTop && (
        <div className="profile__info-top">
          {info.region && (
            <div className="profile__info-top-item">
              <div className="thumb">
                {info.region_icon && <img src={info.region_icon} alt="icon" />}
              </div>
              <div className="content">
                <h2 className="title">{info.region}</h2>
                <span>Region</span>
              </div>
            </div>
          )}
          {info.profile_image && (
            <div className="profile__info-top-item-three">
              <div className="thumb">
                <img src="/img/images/border_left.svg" alt="shape" className="shape" />
                <img src={info.profile_image} alt="img" className="main_img" />
                <img src="/img/images/border_right.svg" alt="shape" className="shape" />
              </div>
              {info.profile_stats && info.profile_stats.length > 0 && (
                <div className="content">
                  <ul className="list-wrap">
                    {info.profile_stats.map((s, i) => (
                      <li key={i}>
                        <img src={s.icon} alt="icon" />
                        <span>{s.value}</span>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          )}
          {info.inventory_value && (
            <div className="profile__info-top-item profile__info-top-item-two">
              <div className="thumb">
                <img src={info.inventory_value.icon} alt="icon" />
              </div>
              <div className="content">
                <h2 className="title">{info.inventory_value.value}</h2>
                <span>{info.inventory_value.label}</span>
              </div>
            </div>
          )}
        </div>
      )}

      {info.ranks && info.ranks.length > 0 && (
        <div className="profile__info-rank">
          <div className="row gutter-y-24 justify-content-center">
            {info.ranks.map((rank, i) => (
              <div key={i} className="col-lg-4 col-md-6">
                <div className="profile__info-rank-item">
                  <div className="icon">
                    <img src={rank.image} alt="icon" />
                  </div>
                  <div className="content">
                    <h2 className="title">{rank.title}</h2>
                    <p>{rank.label}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {info.features && info.features.length > 0 && (
        <div className="profile__info-features">
          {info.features.map((feat, i) => (
            <div key={i} className="profile__info-features-item">
              <div className={feat.red ? 'icon red_icon' : 'icon'}>
                <FeatureIcon icon={feat.icon} />
              </div>
              <div className="content">
                <h2 className="title">{feat.title}</h2>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
