'use strict';

const e = React.createElement;

class MageBuildDetail extends React.Component {
    constructor(props) {
        super(props);
        this.dataUrl = props.dataUrl;
        this.state = {
            build: {
                env_vars: [],
                stages: []
            }
        };
        this.getBuild = this.getBuild.bind(this);
        this.getBuild();
    }

    componentDidMount() {
        this.timerID = setInterval(
            () => this.getBuild(),
            2500
        );
    }

    componentWillUnmount() {
        clearInterval(this.timerID);
    }

    getBuild() {
        const xhr = new XMLHttpRequest();

        xhr.addEventListener('readystatechange', () => {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    this.setState({
                        build: JSON.parse(xhr.responseText),
                    });
                }
            }
        });

        xhr.open('GET', this.dataUrl, true);
        xhr.send();
    }

    render() {
        return (
            <div className="row">
                <div className="col-md-6">
                    <div className="row">
                        <div className="col-md-12">
                            <div className="card card-with-icon">
                                <div className="card-header card-header-warning card-header-icon">
                                    <div className="card-icon">
                                        <i className="material-icons">build</i>
                                    </div>
                                    <h3 className="card-title">Build Stages</h3>
                                </div>
                                <div className="card-body">
                                    <div className="table-responsive">
                                        <table className="table">
                                            <tbody>
                                            {this.state.build.stages.map((stage) =>
                                                <MageBuildDetailStage key={stage._id} stage={stage} />
                                            )}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="col-md-6">
                    <div className="row">
                        <div className="col-md-6">
                            <MageBuildDetailSummary build={this.state.build} />
                        </div>
                        <div className="col-md-6">
                            <MageBuildDetailCheckout build={this.state.build} />
                        </div>
                        <div className="col-md-6">
                            <MageBuildDetailPackaging build={this.state.build} />
                        </div>
                        <div className="col-md-6">
                            <MageBuildDetailEnvVars build={this.state.build} />
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}

class MageBuildDetailStage extends React.Component {
    render() {
        let statusAttribs = {
            className: "material-icons text-info",
            title: "Unknown"
        };
        let statusIcon = 'help';

        if (this.props.stage.status === 'failed') {
            statusAttribs = {
                className: "material-icons text-danger",
                title: "Failed"
            };
            statusIcon = 'cancel';
        } else if (this.props.stage.status === 'successful') {
            statusAttribs = {
                className: "material-icons text-success",
                title: "Successful"
            };
            statusIcon = 'check_circle';
        } else if (this.props.stage.status === 'running') {
            statusAttribs = {
                className: "material-icons text-warning in-progress",
                title: "In progress"
            };
            statusIcon = 'autorenew';
        } else if (this.props.stage.status === 'pending') {
            statusAttribs = {
                className: "material-icons text-info",
                title: "Pending"
            };
            statusIcon = 'hourglass_empty';
        }

        return (
            <tr>
                <td width="30">
                    <i {...statusAttribs}>{statusIcon}</i>
                </td>
                <td width="30">#{this.props.stage.number}</td>
                <td className="font-weight-bold">{this.props.stage.name}</td>

                <td width="125" className="mage-build-detail-stage-number">
                    <MageBuildDetailElapsedTime elapsedSeconds={this.props.stage.elapsedSeconds} />
                </td>

                <td width="50">
                    <button type="button" className="btn btn-success btn-sm">
                        <i className="material-icons">article</i>
                    </button>
                </td>

                <td width="50">
                    <button type="button" className="btn btn-danger btn-sm">
                        <i className="material-icons">bug_report</i>
                    </button>
                </td>
            </tr>
        );
    }
}

class MageBuildDetailSummary extends React.Component {
    render() {
        return (
            <div className="card card-with-icon">
                <div className="card-header card-header-primary card-header-icon">
                    <div className="card-icon">
                        <i className="fa fa-cubes"></i>
                    </div>
                    <h3 className="card-title">
                        Build <code>#{this.props.build.number}</code>
                    </h3>
                </div>
                <div className="card-body">
                    <h4 className="card-title">Status: <code className="mage-build-detail-status">{this.props.build.status}</code></h4>
                    <h4 className="card-title">Created: <code>{this.props.build.created_at}</code></h4>
                    <h4 className="card-title">Started: <code>{this.props.build.started_at_formatted !== null ? this.props.build.started_at_formatted: 'N/A'}</code></h4>
                    <h4 className="card-title">Finished: <code>{this.props.build.finished_at_formatted !== null ? this.props.build.finished_at_formatted: 'N/A'}</code></h4>
                </div>
                <div className="card-footer text-muted">
                    <code><MageBuildDetailElapsedTime elapsedSeconds={this.props.build.elapsed_seconds} /></code>
                </div>
            </div>
        );
    }
}

class MageBuildDetailCheckout extends React.Component {
    render() {
        let status = 'Done';
        if (this.props.build.status === 'pending') {
            status = 'Pending';
        } else if (this.props.build.status === 'checking-out') {
            status = 'In Progress';
        } else if (this.props.build.status === 'checkout-failed') {
            status = 'Failed';
        }

        return (
            <div className="card card-with-icon">
                <div className="card-header card-header-info card-header-icon">
                    <div className="card-icon">
                        <i className="fa fa-git"></i>
                    </div>
                    <h3 className="card-title">Checkout</h3>
                </div>
                <div className="card-body">
                    <h4 className="card-title">Status: <code className="mage-build-detail-status">{status}</code></h4>
                    <h4 className="card-title">Branch/Tag: <code>{this.props.build.branch !== null ? this.props.build.branch: 'N/A'}</code></h4>
                    <h4 className="card-title">Commit: <code title={this.props.build.commit_hash !== null ? this.props.build.commit_hash : 'N/A'}>{this.props.build.commit_short_hash !== null ? this.props.build.commit_short_hash : 'N/A'}</code>&nbsp;<MageBuildDetailCheckoutLink link={this.props.build.commit_link} /></h4>

                    <button type="button" className="btn btn-success btn-sm">
                        <i className="material-icons">article</i>
                        View logs
                    </button>
                    &nbsp;
                    <button type="button" className="btn btn-danger btn-sm">
                        <i className="material-icons">bug_report</i>
                        View error logs
                    </button>
                </div>
            </div>
        );
    }
}

class MageBuildDetailCheckoutLink extends React.Component {
    render() {
        if (this.props.link === null) {
            return(
                <span></span>
            );
        }

        return (
            <a href={this.props.link} target="_blank"><i className="material-icons mage-build-detail-checkout-link">open_in_new</i></a>
        );
    }
}

class MageBuildDetailPackaging extends React.Component {
    render() {
        let packageStatus = 'Pending';
        if (this.props.build.status === 'packaged') {
            packageStatus = 'Done';
        } else if (this.props.build.status === 'releasing') {
            packageStatus = 'Done';
        } else if (this.props.build.status === 'successful') {
            packageStatus = 'Done';
        } else if (this.props.build.status === 'packaging') {
            packageStatus = 'In Progress';
        } else if (this.props.build.status === 'checkout-failed') {
            packageStatus = 'Failed';
        }

        let packageRelease = 'Pending';
        if (this.props.build.status === 'successful') {
            packageRelease = 'Done';
        } else if (this.props.build.status === 'releasing') {
            packageRelease = 'In Progress';
        } else if (this.props.build.status === 'checkout-failed') {
            packageRelease = 'Failed';
        }

        return (
            <div className="card card-with-icon">
                <div className="card-header card-header-rose card-header-icon">
                    <div className="card-icon">
                        <i className="fa fa-truck"></i>
                    </div>
                    <h3 className="card-title">Packaging & Release</h3>
                </div>
                <div className="card-body">
                    <h4 className="card-title">Package: <code className="mage-build-detail-status">{packageStatus}</code></h4>
                    <h4 className="card-title">Release: <code className="mage-build-detail-status">{packageRelease}</code></h4>
                </div>
            </div>
        );
    }
}

class MageBuildDetailEnvVars extends React.Component {
    render() {
        return (
            <div className="card card-with-icon">
                <div className="card-header card-header-terminal card-header-icon">
                    <div className="card-icon">
                        <i className="fa fa-terminal"></i>
                    </div>
                    <h3 className="card-title">Env Vars</h3>
                </div>
                <div className="card-body">
                    {this.props.build.env_vars.map((envVar, index) =>
                        <MageBuildDetailEnvVar key={index} varName={envVar.name} varValue={envVar.value} />
                    )}
                </div>
            </div>
        );
    }
}

class MageBuildDetailEnvVar extends React.Component {
    render() {
        return (
            <div>
                <code>{this.props.varName} : {this.props.varValue}</code>
            </div>
        );
    }
}

class MageBuildDetailElapsedTime extends React.Component {
    render() {
        if (this.props.elapsedSeconds === null) {
            return (
                <code>N/A</code>
            );
        }

        if (this.props.elapsedSeconds < 60) {
            return (
                <code>{this.props.elapsedSeconds} seconds</code>
            );
        }

        let minutes = Math.floor(this.props.elapsedSeconds / 60);
        let seconds = this.props.elapsedSeconds - (minutes * 60);

        return (
            <code>{minutes}min {seconds}sec</code>
        );
    }
}

const domContainer = document.querySelector('#mage-build-detail');
ReactDOM.render(<MageBuildDetail dataUrl={domContainer.getAttribute('data-url')} />, domContainer);

