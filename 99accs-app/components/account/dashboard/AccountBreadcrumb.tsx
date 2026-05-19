import Link from 'next/link';

export function AccountBreadcrumb() {
  return (
    <section
      className="breadcrumb__area-two section__bg"
      style={{ backgroundImage: 'url(/img/bg/breadcrumb_bg.jpg)' }}
    >
      <div className="bg__overlay"></div>
      <div className="container">
        <div className="row">
          <div className="col-12">
            <div className="breadcrumb__content breadcrumb__content-two">
              <nav className="breadcrumb">
                <span>
                  <Link href="/">Home</Link>
                </span>
                <span className="breadcrumb-separator">
                  <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9.93477 7.30953L5.55977 11.6845C5.51913 11.7252 5.47087 11.7574 5.41776 11.7794C5.36465 11.8014 5.30773 11.8127 5.25024 11.8127C5.19276 11.8127 5.13584 11.8014 5.08273 11.7794C5.02962 11.7574 4.98136 11.7252 4.94071 11.6845C4.90006 11.6439 4.86782 11.5956 4.84582 11.5425C4.82382 11.4894 4.8125 11.4325 4.8125 11.375C4.8125 11.3175 4.82382 11.2606 4.84582 11.2075C4.86782 11.1544 4.90006 11.1061 4.94071 11.0655L9.00673 7L4.94071 2.93453C4.85862 2.85244 4.8125 2.7411 4.8125 2.625C4.8125 2.5089 4.85862 2.39756 4.94071 2.31547C5.0228 2.23338 5.13415 2.18726 5.25024 2.18726C5.36634 2.18726 5.47768 2.23338 5.55977 2.31547L9.93477 6.69047C9.97545 6.7311 10.0077 6.77935 10.0297 6.83246C10.0518 6.88557 10.0631 6.9425 10.0631 7C10.0631 7.05749 10.0518 7.11442 10.0297 7.16754C10.0077 7.22065 9.97545 7.2689 9.93477 7.30953Z" fill="currentColor" />
                  </svg>
                </span>
                <span>My account</span>
              </nav>
              <h2 className="title">My account</h2>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
