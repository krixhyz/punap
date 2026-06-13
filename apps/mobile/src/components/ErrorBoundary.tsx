import React, { Component, type ReactNode } from 'react';
import { View, Text, TouchableOpacity } from 'react-native';

interface Props { children: ReactNode; fallbackTitle?: string; }
interface State { hasError: boolean; }

export class ErrorBoundary extends Component<Props, State> {
    constructor(props: Props) {
        super(props);
        this.state = { hasError: false };
    }

    static getDerivedStateFromError(): State { return { hasError: true }; }

    render() {
        if (this.state.hasError) {
            return (
                <View className="flex-1 justify-center items-center px-6 bg-surface">
                    <Text className="text-4xl mb-4">⚠️</Text>
                    <Text className="text-lg font-semibold text-text-primary mb-2 text-center">
                        {this.props.fallbackTitle ?? 'Something went wrong'}
                    </Text>
                    <TouchableOpacity
                        className="bg-primary rounded-xl py-3 px-6 mt-4"
                        onPress={() => this.setState({ hasError: false })}
                    >
                        <Text className="text-white font-semibold">Retry</Text>
                    </TouchableOpacity>
                </View>
            );
        }
        return this.props.children;
    }
}
