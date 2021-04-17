'use strict';

const e = React.createElement;

class MageProjects extends React.Component {
    constructor(props) {
        super(props);
        this.dataUrl = props.dataUrl;
        this.state = {
            projects: []
        };
        this.getProjects = this.getProjects.bind(this);
        this.getProjects();
    }

    componentDidMount() {
        this.timerID = setInterval(
            () => this.getProjects(),
            30000
        );
    }

    componentWillUnmount() {
        clearInterval(this.timerID);
    }

    getProjects() {
        const xhr = new XMLHttpRequest();

        xhr.addEventListener('readystatechange', () => {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    this.setState({
                        projects: JSON.parse(xhr.responseText),
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
                {this.state.projects.map((project) =>
                    <MageProject key={project._id} project={project} getProjects={this.getProjects} />
                )}
            </div>
        );
    }
}

class MageProject extends React.Component {
    render() {
        let settingsAttrib = {
            href: this.props.project._href,
            title: 'Configure project'
        };

        let addEnvAttribs = {
            href: this.props.project._href_new_env
        };


        return (
            <div className="col-md-6">
                <div className="card">
                    <div className="card-header card-header-primary">
                        <span className="float-right">
                            <a {...settingsAttrib} className="btn btn-warning btn-sm">
                                <i className="material-icons">settings</i>
                            </a>
                        </span>
                        <h4 className="card-title ">{this.props.project.name} ({this.props.project.code})</h4>
                        <p className="card-category">{this.props.project.description}</p>
                    </div>
                    <div className="card-body">
                        <div className="table-responsive">
                            <table className="table">
                                <thead className=" text-primary">
                                    <tr>
                                        <th>Environment</th>
                                        <th>Code</th>
                                        <th>Last Success</th>
                                        <th>Last Failure</th>
                                        <th>Last Duration</th>
                                        <th />
                                    </tr>
                                </thead>
                                <tbody>
                                    {this.props.project.environments.map((environment) =>
                                        <MageProjectEnvironment key={environment._id} environment={environment} getProjects={this.props.getProjects} />
                                    )}
                                </tbody>
                            </table>

                            <div className="float-right">
                                <a className="btn btn-info btn-sm" {...addEnvAttribs}>
                                    Add environment
                                </a>
                            </div>
                            <div className="clearfix" />
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}


class MageProjectEnvironment extends React.Component {
    render() {
        let envAttribs = {
            href: this.props.environment._href
        };

        let buildButtonsAttribs = {
            href: this.props.environment._href_builds
        };

        return (
            <tr>
                <td className="font-weight-bold">
                    <a {...envAttribs}>{this.props.environment.name}</a>
                </td>
                <td>{this.props.environment.code}</td>
                <td><MageProjectEnvironmentBuild type="success" build={this.props.environment.last_success} /></td>
                <td><MageProjectEnvironmentBuild type="danger" build={this.props.environment.last_failure} /></td>
                <td><MageElapsedTime selfUpdate={this.props.environment.is_running} seconds={this.props.environment.last_duration} /></td>
                <td>
                    <a title="View builds" {...buildButtonsAttribs} className="btn btn-info btn-sm">
                        <i className="material-icons">view_module</i>
                    </a>
                    &nbsp;
                    <MageProjectEnvironmentDeployButton environment={this.props.environment} getProjects={this.props.getProjects} />
                </td>
            </tr>
        );
    }
}

class MageProjectEnvironmentBuild extends React.Component {
    render() {
        if (this.props.build === null) {
            return (
                <span>N/A</span>
            );
        }

        let attribs = {
            className: `${this.props.type}-link`,
            href: this.props.build._href
        };

        return (
            <span>
                <MageDate date={this.props.build.created_at} />
                &nbsp;/&nbsp;
                <a {...attribs}>#{this.props.build.number}</a>
            </span>
        );
    }
}

class MageProjectEnvironmentDeployButton extends React.Component {
    requestDeploy(environment, callback) {
        $.ajax({
            dataType: 'json',
            url: environment._href_deploy,
            method: 'POST',
            success: (response) => {
                $.notify({
                    icon: 'add_alert',
                    message: 'Build requested'
                }, {
                    type: 'success',
                    timer: 3000,
                    placement: { from: 'top', align: 'right' }
                });

                callback();
            }
        });
    }

    render() {
        if (this.props.environment.is_running) {
            let attribs = {
                href: this.props.environment._href_running_build
            };

            return (
                <a className="btn btn-primary btn-sm" title="View build details" {...attribs}>
                    <i className="material-icons in-progress">autorenew</i>
                </a>
            );
        }

        let attribs = {
            'data-action': 'request-deploy',
            'data-request-deploy': this.props.environment._href_deploy,
            title: `Build and Deploy ${this.props.environment.branch} branch`
        };

        return (
            <button type="button" className="btn btn-success btn-sm" {...attribs} onClick={(e) => { this.requestDeploy(this.props.environment, this.props.getProjects) }}>
                <i className="material-icons">play_circle</i>
            </button>
        );
    }
}

class MageElapsedTime extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            seconds: props.seconds,
            initialSeconds: props.seconds
        };
    }

    componentDidMount() {
        if (this.props.selfUpdate) {
            this.timerID = setInterval(
                () => this.selfUpdateElapsedTime(),
                1000
            );
        }
    }

    componentWillUnmount() {
        if (this.timerID) {
            clearInterval(this.timerID);
        }
    }

    componentDidUpdate(prevProps, prevState, snapshot) {
        if (!prevProps.selfUpdate && this.timerID) {
            clearInterval(this.timerID);
            this.timerID = null;
        }

        if (prevProps.selfUpdate && !this.timerID) {
            this.timerID = setInterval(
                () => this.selfUpdateElapsedTime(),
                1000
            );
        }
    }

    selfUpdateElapsedTime() {
        this.setState({
            seconds: this.state.seconds + 1
        });
    }

    static getDerivedStateFromProps(props, state) {
        if (state.initialSeconds !== props.seconds) {
            return {
                seconds: props.seconds,
                initialSeconds: props.seconds
            };
        }

        return null;
    }

    render() {
        if (this.props.seconds === null) {
            return (
                <code>N/A</code>
            );
        }

        let elapsedTime = this.state.seconds;

        if (elapsedTime < 60) {
            return (
                <code>{elapsedTime} seconds</code>
            );
        }

        let minutes = Math.floor(elapsedTime / 60);
        let seconds = elapsedTime - (minutes * 60);

        return (
            <code>{minutes}min {seconds}sec</code>
        );
    }
}

class MageDate extends React.Component {
    render() {
        if (this.props.date === null) {
            return (
                <span>N/A</span>
            );
        }

        let current = new Date();
        let event = new Date(this.props.date);

        let ye = new Intl.DateTimeFormat('en', { year: 'numeric' }).format(event);
        let mo = new Intl.DateTimeFormat('en', { month: 'short' }).format(event);
        let da = new Intl.DateTimeFormat('en', { day: '2-digit' }).format(event);
        let dateToShow = `${da}-${mo}-${ye}`;

        let days = (current.getTime() - event.getTime()) / 1000 / 3600 / 24;

        if (days < 1) {
            dateToShow = 'Today';
        } else if (days < 2) {
            dateToShow = 'Yesterday';
        } else if (days < 14) {
            days = parseInt(days);
            dateToShow = `${days} days ago`;
        } else if (days < 31) {
            let weeks = parseInt(days / 7);
            dateToShow = `${weeks} weeks ago`;
        } else if (days < 360) {
            let months = parseInt(days / 30);
            dateToShow = `${months} months ago`;
        }

        let attribs = {
            title: this.props.date
        };

        return (
            <span {...attribs}>{dateToShow}</span>
        );
    }
}

const domContainer = document.querySelector('#mage-projects');
ReactDOM.render(<MageProjects dataUrl={domContainer.getAttribute('data-url')} />, domContainer);

