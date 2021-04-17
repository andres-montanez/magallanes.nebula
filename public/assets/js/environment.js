'use strict';

const e = React.createElement;

class MageEnvironmentBuilds extends React.Component {
    constructor(props) {
        super(props);
        this.dataUrl = props.dataUrl;
        this.state = {
            builds: []
        };
        this.getBuilds = this.getBuilds.bind(this);
        this.getBuilds();
    }

    componentDidMount() {
        this.timerID = setInterval(
            () => this.getBuilds(),
            15000
        );
    }

    componentWillUnmount() {
        clearInterval(this.timerID);
    }

    getBuilds() {
        const xhr = new XMLHttpRequest();

        xhr.addEventListener('readystatechange', () => {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    this.setState({
                        builds: JSON.parse(xhr.responseText),
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
                <div className="col-md-12">
                    <div className="card">
                        <div className="card-header card-header-primary">
                            <h4 className="card-title ">Builds</h4>
                        </div>
                        <div className="card-body">
                            <div className="table-responsive">
                                <table className="table">
                                    <thead className=" text-primary">
                                    <tr>
                                        <th width="1">#</th>
                                        <th width="100">Status</th>
                                        <th width="100">Commit</th>
                                        <th>Message</th>
                                        <th width="200">Requested by</th>
                                        <th width="150">Requested at</th>
                                        <th width="150">Started</th>
                                        <th width="150">Finished</th>
                                        <th width="125">Time</th>
                                        <th width="225" />
                                    </tr>
                                    </thead>

                                    <tbody>
                                        {this.state.builds.map((build) =>
                                            <MageEnvironmentBuild key={build._id} build={build} getBuilds={this.getBuilds} />
                                        )}
                                    </tbody>
                                </table>
                                <div className="clearfix" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}

class MageEnvironmentBuild extends React.Component {
    deleteBuild(build, callback) {
        $.ajax({
            dataType: 'json',
            url: build._href_delete,
            method: 'POST',
            success: (response) => {
                $.notify({
                    icon: 'add_alert',
                    message: 'Build requested for deletion'
                }, {
                    type: 'success',
                    timer: 3000,
                    placement: { from: 'top', align: 'right' }
                });

                callback();
            }
        });
    }

    rollbackBuild(build, callback) {
        $.ajax({
            dataType: 'json',
            url: build._href_rollback,
            method: 'POST',
            success: (response) => {
                $.notify({
                    icon: 'add_alert',
                    message: 'Rollback of build queued'
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
        let rowAttrib = {
            id: `build-${this.props.build._id}`
        };

        let viewAttribs = {
            href: this.props.build._href,
            title: 'View details'
        };

        let rollbackAttribs = {
            title: 'Rollback to this build'
        };

        if (this.props.build.is_successful === false) {
            rollbackAttribs.disabled = 'disabled';
        }

        return (
            <tr {...rowAttrib}>
                <td className="mage-env-build-number">{this.props.build.number}</td>
                <td className="mage-env-build-status">{this.props.build.status}</td>
                <td>{this.props.build.commit_short_hash}</td>
                <td>
                    {this.props.build.rollback_number !== null ? `[Rollback of #${this.props.build.rollback_number}] ` : ''}
                    {this.props.build.commit_message}
                </td>
                <td>{this.props.build.requested_by}</td>
                <td>{this.props.build.created_at}</td>
                <td>{this.props.build.started_at_formatted !== null ? this.props.build.started_at_formatted: 'N/A'}</td>
                <td>{this.props.build.finished_at_formatted !== null ? this.props.build.finished_at_formatted: 'N/A'}</td>
                <td><MageEnvironmentBuildElapsedTime started={this.props.build.started_at} finished={this.props.build.finished_at} /></td>
                <td>
                    <a className="btn btn-success btn-sm" {...viewAttribs}>
                        <i className="material-icons">visibility</i>
                    </a>
                    &nbsp;
                    <button type="button" className="btn btn-warning btn-sm" {...rollbackAttribs} onClick={(e) => { this.rollbackBuild(this.props.build, this.props.getBuilds) }}>
                        <i className="material-icons">settings_backup_restore</i>
                    </button>
                    &nbsp;
                    <button type="button" className="btn btn-danger btn-sm" title="Delete build" onClick={(e) => { this.deleteBuild(this.props.build, this.props.getBuilds) }}>
                        <i className="material-icons">delete</i>
                    </button>
                </td>
            </tr>
        );
    }
}

class MageEnvironmentBuildElapsedTime extends React.Component {
    componentDidMount() {
        if (this.props.started !== null && this.props.finished === null) {
            this.timerID = setInterval(
                () => this.updateElapsedTime(),
                1000
            );
        } else if (this.timerID) {
            clearInterval(this.timerID);
        }
    }

    componentWillUnmount() {
        if (this.timerID) {
            clearInterval(this.timerID);
        }
    }

    componentDidUpdate(prevProps, prevState, snapshot) {
        if (prevProps.started !== null && prevProps.finished === null) {
            if (!this.timerID) {
                this.timerID = setInterval(
                    () => this.updateElapsedTime(),
                    1000
                );
            }
        } else if (this.timerID) {
            clearInterval(this.timerID);
        }
    }

    updateElapsedTime() {
        this.forceUpdate();
    }

    render() {
        if (this.props.started === null) {
            return (
                <code>not started</code>
            );
        }

        let startedAt = new Date(this.props.started);
        let finishedAt = new Date();
        if (this.props.finished !== null) {
            finishedAt = new Date(this.props.finished);
        }

        let elapsedTime = parseInt((finishedAt.getTime() - startedAt.getTime()) / 1000);

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

const domContainer = document.querySelector('#mage-environment-builds');
ReactDOM.render(<MageEnvironmentBuilds dataUrl={domContainer.getAttribute('data-url')} />, domContainer);

