export function AccountDetailsPane() {
  return (
    <div id="tab4" className="account-pane active">
      <form action="#" className="account-form customer__form-wrap customer__form-wrap-two">
        <div className="row">
          <div className="col-md-6">
            <div className="form-grp">
              <label htmlFor="firstName">First name <span>*</span></label>
              <input type="text" id="firstName" />
            </div>
          </div>
          <div className="col-md-6">
            <div className="form-grp">
              <label htmlFor="lastName">Last name <span>*</span></label>
              <input type="text" id="lastName" />
            </div>
          </div>
        </div>
        <div className="form-grp">
          <label htmlFor="displayName">Display name <span>*</span></label>
          <input type="text" id="displayName" placeholder="Kellyburn" />
        </div>
        <div className="form-grp">
          <label htmlFor="emailAddress">Email address <span>*</span></label>
          <input type="email" id="emailAddress" placeholder="kelly_burn@demo.com" />
        </div>
        <h2 className="title">Password change</h2>
        <div className="form-grp">
          <label htmlFor="currentPassword">Current password (leave blank to leave unchanged)</label>
          <input type="password" id="currentPassword" />
        </div>
        <div className="form-grp">
          <label htmlFor="newPassword">New password (leave blank to leave unchanged)</label>
          <input type="password" id="newPassword" />
        </div>
        <div className="form-grp">
          <label htmlFor="confirmPassword">Confirm new password</label>
          <input type="password" id="confirmPassword" />
        </div>
        <button type="submit" className="tg-btn">Save changes</button>
      </form>
    </div>
  );
}
